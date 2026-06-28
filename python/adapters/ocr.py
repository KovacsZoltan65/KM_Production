"""OCR adapter boundary for the Python AI Engine."""

from pathlib import Path
from typing import Any


def empty_ocr_data(backend: str | None = None) -> dict[str, Any]:
    return {
        "text": "",
        "language": "unknown",
        "pages": [],
        "backend": backend,
    }


def unavailable_error() -> dict[str, str]:
    return {
        "code": "ocr_backend_unavailable",
        "message": "No OCR backend is configured or available.",
    }


def run_ocr(document: dict[str, Any], options: dict[str, Any] | None = None) -> dict[str, Any]:
    options = options or {}
    backend = options.get("backend")

    if backend != "stub":
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data(None),
            "errors": [unavailable_error()],
        }

    path_value = document.get("path")
    if not isinstance(path_value, str) or not path_value:
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data("stub"),
            "errors": [
                {
                    "code": "document_path_missing",
                    "message": "Document path is required for OCR.",
                }
            ],
        }

    path = Path(path_value)
    if not path.exists() or not path.is_file():
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data("stub"),
            "errors": [
                {
                    "code": "document_file_missing",
                    "message": "Document file is not available for OCR.",
                }
            ],
        }

    max_text_bytes = int(options.get("max_text_bytes") or 20000)
    max_text_bytes = max(1, min(max_text_bytes, 200000))

    filename = str(document.get("filename") or path.name).lower()
    mime_type = str(document.get("mime_type") or "").lower()

    if path.suffix.lower() != ".txt" and mime_type != "text/plain" and not filename.endswith(".txt"):
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data("stub"),
            "errors": [
                {
                    "code": "ocr_file_type_unsupported",
                    "message": "Stub OCR supports plain text files only.",
                }
            ],
        }

    with path.open("rb") as handle:
        text = handle.read(max_text_bytes).decode("utf-8", errors="replace")

    return {
        "success": True,
        "confidence": 0.75,
        "data": {
            "text": text,
            "language": "unknown",
            "pages": [],
            "backend": "stub",
        },
        "errors": [],
    }
