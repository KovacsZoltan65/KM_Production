"""OCR adapter boundary for the Python AI Engine."""

from typing import Any

from adapters.ocr_backends import get_backend


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


def unknown_backend_error(backend: str | None) -> dict[str, str]:
    return {
        "code": "ocr_backend_unknown",
        "message": f"OCR backend is not supported: {backend or 'none'}.",
    }


def run_ocr(document: dict[str, Any], options: dict[str, Any] | None = None) -> dict[str, Any]:
    options = options or {}
    backend = options.get("backend")

    if backend is None:
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data(None),
            "errors": [unavailable_error()],
        }

    if not isinstance(backend, str):
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data(None),
            "errors": [unknown_backend_error(None)],
        }

    selected_backend = get_backend(backend)
    if selected_backend is None:
        return {
            "success": False,
            "confidence": 0.0,
            "data": empty_ocr_data(None),
            "errors": [unknown_backend_error(backend)],
        }

    backend_result = selected_backend.extract(document, options)

    return {
        "success": backend_result["success"],
        "confidence": backend_result["confidence"],
        "data": {
            "text": backend_result["text"],
            "language": backend_result["language"],
            "pages": backend_result["pages"],
            "backend": backend_result["backend"],
        },
        "errors": backend_result["errors"],
    }
