"""Stub OCR backend for deterministic local testing."""

from pathlib import Path
from typing import Any

from adapters.ocr_backends.base import OcrBackend, ocr_backend_result


class StubOcrBackend(OcrBackend):
    name = "stub"

    def extract(self, document: dict[str, Any], options: dict[str, Any]) -> dict[str, Any]:
        path_value = document.get("path")
        if not isinstance(path_value, str) or not path_value:
            return ocr_backend_result(
                success=False,
                confidence=0.0,
                backend=self.name,
                errors=[
                    {
                        "code": "document_path_missing",
                        "message": "Document path is required for OCR.",
                    }
                ],
            )

        path = Path(path_value)
        if not path.exists() or not path.is_file():
            return ocr_backend_result(
                success=False,
                confidence=0.0,
                backend=self.name,
                errors=[
                    {
                        "code": "document_file_missing",
                        "message": "Document file is not available for OCR.",
                    }
                ],
            )

        max_text_bytes = int(options.get("max_text_bytes") or 20000)
        max_text_bytes = max(1, min(max_text_bytes, 200000))

        filename = str(document.get("filename") or path.name).lower()
        mime_type = str(document.get("mime_type") or "").lower()

        if path.suffix.lower() != ".txt" and mime_type != "text/plain" and not filename.endswith(".txt"):
            return ocr_backend_result(
                success=False,
                confidence=0.0,
                backend=self.name,
                errors=[
                    {
                        "code": "ocr_file_type_unsupported",
                        "message": "Stub OCR supports plain text files only.",
                    }
                ],
            )

        with path.open("rb") as handle:
            text = handle.read(max_text_bytes).decode("utf-8", errors="replace")

        return ocr_backend_result(
            success=True,
            confidence=0.75,
            text=text,
            backend=self.name,
        )
