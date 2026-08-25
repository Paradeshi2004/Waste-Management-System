# Waste Management System with AI

A web-based waste management platform built with PHP, MySQL, JavaScript, and FastAPI, with an AI-powered waste image classification service.

## Overview

The system allows residents to submit waste complaints, upload waste images, track complaint status, receive notifications, and provide feedback after resolution.

Administrators can manage complaints, update status and priority, add notes, and monitor the system.

The separate AI service classifies uploaded waste images using the Hugging Face model:

`watersplash/waste-classification`

## Features

### Resident

- User registration and login
- Submit waste complaints
- Upload waste images
- AI-assisted waste classification
- Automatic location detection
- View complaint history
- Track complaint status and priority
- Receive notifications
- Submit feedback after resolution
- View waste-management tips
- Manage profile

### Administrator

- Admin authentication
- View and manage complaints
- Filter complaints by status and category
- Update complaint status
- Change complaint priority
- Add administrative notes
- View complaint update history
- Send resident notifications
- View dashboard statistics
- Manage waste-management tips

### AI Service

- FastAPI REST API
- Waste image classification
- JPG, PNG, and WEBP validation
- Image validation using Pillow
- Prediction confidence score
- Top 3 predictions
- Apple MPS GPU support when available
- CPU fallback

## Technology Stack

| Component | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| Web Server | Apache / XAMPP |
| AI API | Python + FastAPI |
| AI Model | Hugging Face `watersplash/waste-classification` |
| Image Processing | Pillow |
| Machine Learning | PyTorch + Transformers |
| Version Control | Git + GitHub |

## Project Structure

```text
Waste-Management-Complete/
│
├── Waste-Management-AI/
│   ├── main.py
│   └── requirements.txt
│
├── Waste-Management-System/
│   ├── admin/
│   ├── css/
│   ├── includes/
│   ├── js/
│   ├── pages/
│   ├── sql/
│   │   └── wms.sql
│   ├── uploads/
│   ├── index.php
│   └── README.md
│
└── .gitignore
