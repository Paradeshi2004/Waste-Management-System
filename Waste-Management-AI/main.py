from fastapi import FastAPI, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from PIL import Image
from transformers import AutoImageProcessor, AutoModelForImageClassification
import torch
import io

app = FastAPI(
    title="Waste Management AI",
    description="AI service for waste image classification",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost",
        "http://127.0.0.1",
    ],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ---------------------------------------------------------
# Model configuration
# ---------------------------------------------------------

MODEL_NAME = "watersplash/waste-classification"

# Use Apple's GPU when available
if torch.backends.mps.is_available():
    DEVICE = torch.device("mps")
else:
    DEVICE = torch.device("cpu")

print(f"Using device: {DEVICE}")
print(f"Loading model: {MODEL_NAME}")

processor = AutoImageProcessor.from_pretrained(MODEL_NAME)

model = AutoModelForImageClassification.from_pretrained(
    MODEL_NAME
)

model.to(DEVICE)
model.eval()

print("Waste classification model loaded successfully.")


# ---------------------------------------------------------
# Helper
# ---------------------------------------------------------

def clean_label(label: str) -> str:
    return label.replace("_", " ").replace("-", " ").title()


# ---------------------------------------------------------
# Home
# ---------------------------------------------------------

@app.get("/")
def root():
    return {
        "message": "Waste Management AI Service is running",
        "status": "ok",
        "model": MODEL_NAME,
        "device": str(DEVICE)
    }


# ---------------------------------------------------------
# Health
# ---------------------------------------------------------

@app.get("/health")
def health():
    return {
        "status": "healthy",
        "model": MODEL_NAME,
        "device": str(DEVICE)
    }


# ---------------------------------------------------------
# Waste classification
# ---------------------------------------------------------

@app.post("/classify")
async def classify_waste(file: UploadFile = File(...)):

    allowed_types = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ]

    if file.content_type not in allowed_types:
        raise HTTPException(
            status_code=400,
            detail="Only JPG, PNG and WEBP images are allowed."
        )

    contents = await file.read()

    try:
        image = Image.open(
            io.BytesIO(contents)
        ).convert("RGB")

    except Exception:
        raise HTTPException(
            status_code=400,
            detail="Invalid image file."
        )

    # Prepare image
    inputs = processor(
        images=image,
        return_tensors="pt"
    )

    # Move tensors to MPS/CPU
    inputs = {
        key: value.to(DEVICE)
        for key, value in inputs.items()
    }

    # Run model
    with torch.no_grad():

        outputs = model(**inputs)

        probabilities = torch.nn.functional.softmax(
            outputs.logits,
            dim=-1
        )

    # Get top prediction
    confidence, predicted_class = torch.max(
        probabilities,
        dim=-1
    )

    class_id = predicted_class.item()

    label = model.config.id2label[class_id]

    confidence_value = float(
        confidence.item()
    )

    # Get top 3 predictions
    top_values, top_indices = torch.topk(
        probabilities,
        k=min(3, probabilities.shape[-1])
    )

    top_predictions = []

    for value, index in zip(
        top_values[0],
        top_indices[0]
    ):

        top_predictions.append({
            "category": clean_label(
                model.config.id2label[index.item()]
            ),
            "confidence": round(
                float(value.item()) * 100,
                2
            )
        })

    return {
        "success": True,
        "filename": file.filename,
        "prediction": {
            "category": clean_label(label),
            "confidence": round(
                confidence_value * 100,
                2
            )
        },
        "top_predictions": top_predictions
    }