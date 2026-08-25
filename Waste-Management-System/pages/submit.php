<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/complaints.php';
requireLogin();
$user = currentUser();

$csrfToken = getCsrfToken();

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken()) {
        $error = 'Invalid CSRF token.';
    } else {

        $data = [
        'user_id'     => $user['id'],
        'title'       => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category'    => $_POST['category'] ?? 'garbage',
        'location'    => trim($_POST['location'] ?? ''),
        'latitude'    => !empty($_POST['latitude']) ? $_POST['latitude'] : null,
        'longitude'   => !empty($_POST['longitude']) ? $_POST['longitude'] : null,
        'priority'    => $_POST['priority'] ?? 'medium',

        // AI classification
        'ai_category'   => $_POST['ai_category'] ?? null,
        'ai_confidence' => $_POST['ai_confidence'] ?? null,
    ];

    if (empty($data['title']) || empty($data['description']) || empty($data['location'])) {
        $error = "Title, description, and location are required.";
    } else {
        $id = submitComplaint($data, $_FILES['image'] ?? null);
        header('Location: ' . APP_URL . '/pages/complaint.php?id=' . $id . '&submitted=1');
        exit;
    }
}
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-4"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Report a Waste Issue</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= sanitize($csrfToken) ?>"
                    >

                    <input type="hidden" name="ai_category" id="aiCategoryInput">
                    <input type="hidden" name="ai_confidence" id="aiConfidenceInput">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Issue Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               placeholder="e.g. Overflowing garbage bin on Main St."
                               value="<?= sanitize($_POST['title'] ?? '') ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category" class="form-select">
                                <option value="garbage">Garbage / Trash</option>
                                <option value="illegal_dumping">Illegal Dumping</option>
                                <option value="recycling">Recycling Issue</option>
                                <option value="hazardous">Hazardous Waste</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Describe the issue in detail..."
                                  required><?= sanitize($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                        <input type="text" name="location" id="locationInput" class="form-control"
                               placeholder="Enter address or area name"
                               value="<?= sanitize($_POST['location'] ?? '') ?>" required>
                        <input type="hidden" name="latitude" id="latInput">
                        <input type="hidden" name="longitude" id="lngInput">
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="detectLocation">
                            <i class="bi bi-geo-alt me-1"></i>Use My Location
                        </button>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Upload Photo (optional)</label>

                        <input type="file"
                            name="image"
                            id="wasteImage"
                            class="form-control"
                            accept="image/*">

                        <div class="form-text">
                            Max 5MB. JPG, PNG, GIF, WEBP accepted.
                        </div>

                        <!-- Image Preview -->
                        <div id="imagePreview" class="mt-3 d-none">
                            <img id="previewImage"
                                src=""
                                alt="Selected waste image"
                                class="img-thumbnail"
                                style="max-width: 250px; max-height: 250px;">
                        </div>

                        <!-- AI Loading -->
                        <div id="aiLoading" class="alert alert-info mt-3 d-none">
                            <i class="bi bi-hourglass-split me-2"></i>
                            AI is analyzing the waste image...
                        </div>

                        <!-- AI Error -->
                        <div id="aiError" class="alert alert-danger mt-3 d-none"></div>

                        <!-- AI Result -->
                        <div id="aiResult" class="alert alert-success mt-3 d-none">

                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-robot me-2"></i>
                                AI Waste Detection
                            </h5>

                            <div class="row">

                                <div class="col-md-4">
                                    <strong>Detected Waste:</strong>
                                    <div id="aiWasteType" class="fw-bold text-success">
                                        -
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <strong>Confidence:</strong>
                                    <div id="aiConfidence" class="fw-bold">
                                        -
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <strong>Suggested Category:</strong>
                                    <div id="aiSuggestedCategory" class="fw-bold">
                                        -
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success fw-semibold">
                            <i class="bi bi-send me-1"></i>Submit Report
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

// =====================================================
// YOUR EXISTING LOCATION DETECTION CODE
// =====================================================

document.getElementById('detectLocation').addEventListener('click', function() {

    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser.');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Detecting...';

    const btn = this;

    navigator.geolocation.getCurrentPosition(pos => {

        document.getElementById('latInput').value =
            pos.coords.latitude;

        document.getElementById('lngInput').value =
            pos.coords.longitude;

        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${pos.coords.latitude}&lon=${pos.coords.longitude}&format=json`)
            .then(r => r.json())
            .then(data => {

                if (data.display_name) {
                    document.getElementById('locationInput').value =
                        data.display_name;
                }

            })
            .catch(() => {

                document.getElementById('locationInput').value =
                    `${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`;

            })
            .finally(() => {

                btn.disabled = false;

                btn.innerHTML =
                    '<i class="bi bi-geo-alt-fill me-1 text-success"></i>Location Detected';

            });

    }, () => {

        alert('Unable to detect location. Please enter manually.');

        btn.disabled = false;

        btn.innerHTML =
            '<i class="bi bi-geo-alt me-1"></i>Use My Location';

    });

});


// =====================================================
// AI WASTE CLASSIFICATION
// =====================================================

const wasteImage = document.getElementById('wasteImage');

const aiResult = document.getElementById('aiResult');

const aiLoading = document.getElementById('aiLoading');

const aiError = document.getElementById('aiError');


if (wasteImage) {

    wasteImage.addEventListener('change', async function () {

        const file = this.files[0];

        aiResult.classList.add('d-none');
        aiError.classList.add('d-none');
        aiLoading.classList.add('d-none');

        if (!file) {
            return;
        }

        if (file.size > 5 * 1024 * 1024) {

            aiError.textContent =
                'Image must be smaller than 5MB.';

            aiError.classList.remove('d-none');

            this.value = '';

            return;
        }

        if (!file.type.startsWith('image/')) {

            aiError.textContent =
                'Please select a valid image file.';

            aiError.classList.remove('d-none');

            this.value = '';

            return;
        }


        aiLoading.classList.remove('d-none');


        const formData = new FormData();

        formData.append('file', file);


        try {

            const response = await fetch(
                'http://127.0.0.1:8000/classify',
                {
                    method: 'POST',
                    body: formData
                }
            );


            if (!response.ok) {
                throw new Error('AI server returned an error.');
            }


            const data = await response.json();

            console.log('AI Response:', data);


            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Unable to classify image.'
                );

            }


            const prediction = data.prediction;

            const wasteType = prediction.category;

            const confidence = prediction.confidence;


            document.getElementById('aiWasteType').textContent =
                wasteType;

            document.getElementById('aiConfidence').textContent =
                confidence + '%';

            document.getElementById('aiCategoryInput').value =
                wasteType;

            document.getElementById('aiConfidenceInput').value =
                confidence;


            // Convert AI result into your complaint category

            let suggestedCategory = 'other';

            const waste = wasteType.toLowerCase();


            if (
                waste.includes('plastic') ||
                waste.includes('paper') ||
                waste.includes('cardboard') ||
                waste.includes('glass') ||
                waste.includes('metal') ||
                waste.includes('clothes') ||
                waste.includes('shoes')
            ) {

                suggestedCategory = 'recycling';

            }


            if (
                waste.includes('battery') ||
                waste.includes('hazardous') ||
                waste.includes('chemical')
            ) {

                suggestedCategory = 'hazardous';

            }


            if (
                waste.includes('garbage') ||
                waste.includes('trash')
            ) {

                suggestedCategory = 'garbage';

            }


            const categorySelect =
                document.querySelector(
                    'select[name="category"]'
                );


            if (categorySelect) {

                categorySelect.value =
                    suggestedCategory;

            }


            const categoryText = {

                garbage: 'Garbage / Trash',

                illegal_dumping: 'Illegal Dumping',

                recycling: 'Recycling Issue',

                hazardous: 'Hazardous Waste',

                other: 'Other'

            };


            document.getElementById(
                'aiSuggestedCategory'
            ).textContent =
                categoryText[suggestedCategory];


            aiLoading.classList.add('d-none');

            aiResult.classList.remove('d-none');


        } catch (error) {

            console.error(
                'AI classification error:',
                error
            );


            aiLoading.classList.add('d-none');


            aiError.textContent =
                'AI detection failed. You can still submit the complaint manually.';


            aiError.classList.remove('d-none');

        }

    });

}

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
