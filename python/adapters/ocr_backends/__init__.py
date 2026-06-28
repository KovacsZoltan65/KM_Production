"""OCR backend plugin registry."""

from adapters.ocr_backends.base import OcrBackend, ocr_backend_result
from adapters.ocr_backends.stub import StubOcrBackend


def get_backend(name: str | None) -> OcrBackend | None:
    backends: dict[str, OcrBackend] = {
        "stub": StubOcrBackend(),
    }

    if name is None:
        return None

    return backends.get(name)


__all__ = ["OcrBackend", "get_backend", "ocr_backend_result"]
