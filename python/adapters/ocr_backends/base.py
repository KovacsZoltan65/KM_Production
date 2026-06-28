"""Base OCR backend contract."""

from abc import ABC, abstractmethod
from typing import Any


def ocr_backend_result(
    success: bool,
    confidence: float,
    text: str = "",
    language: str = "unknown",
    pages: list[dict[str, Any]] | None = None,
    backend: str | None = None,
    errors: list[dict[str, str]] | None = None,
) -> dict[str, Any]:
    return {
        "success": success,
        "confidence": confidence,
        "text": text,
        "language": language,
        "pages": pages or [],
        "backend": backend,
        "errors": errors or [],
    }


class OcrBackend(ABC):
    name: str

    @abstractmethod
    def extract(self, document: dict[str, Any], options: dict[str, Any]) -> dict[str, Any]:
        """Extract text from a document and return an OCR backend result."""
