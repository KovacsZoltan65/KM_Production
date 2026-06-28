#!/usr/bin/env python3
"""Minimal JSON-only Python AI Engine proof of concept."""

import json
import sys
from typing import Any


ENGINE = "python-ai-engine"
VERSION = "0.1.0"


def response(
    success: bool,
    task: str | None,
    confidence: float,
    data: dict[str, Any] | None = None,
    errors: list[dict[str, str]] | None = None,
    extra: dict[str, Any] | None = None,
) -> dict[str, Any]:
    payload = {
        "success": success,
        "engine": ENGINE,
        "version": VERSION,
        "task": task,
        "confidence": confidence,
        "data": data or {},
        "errors": errors or [],
    }

    if extra:
        payload.update(extra)

    return payload


def classify_filename(filename: str) -> dict[str, Any]:
    normalized = filename.lower()

    if "delivery" in normalized or "delivery_note" in normalized:
        return {
            "classification": "unknown",
            "confidence": 0.82,
            "suggested_type": "delivery_note",
        }

    if "invoice" in normalized:
        return {
            "classification": "unknown",
            "confidence": 0.82,
            "suggested_type": "supplier_document",
        }

    if "quality" in normalized or "inspection" in normalized:
        return {
            "classification": "unknown",
            "confidence": 0.82,
            "suggested_type": "quality_report",
        }

    if "drawing" in normalized:
        return {
            "classification": "unknown",
            "confidence": 0.82,
            "suggested_type": "drawing",
        }

    return {
        "classification": "unknown",
        "confidence": 0.5,
        "suggested_type": "other",
    }


def handle(payload: dict[str, Any]) -> dict[str, Any]:
    task = payload.get("task")

    if task == "health_check":
        return response(
            success=True,
            task="health_check",
            confidence=1.0,
            data={"message": "Python AI Engine is reachable"},
        )

    if task == "document_classification":
        document = payload.get("document")

        if not isinstance(document, dict) or not isinstance(document.get("filename"), str):
            return response(
                success=False,
                task="document_classification",
                confidence=0.0,
                errors=[
                    {
                        "code": "invalid_document_payload",
                        "message": "Document filename is required.",
                    }
                ],
            )

        classification = classify_filename(document["filename"])

        return response(
            success=True,
            task="document_classification",
            confidence=classification["confidence"],
            data={"suggested_type": classification["suggested_type"]},
            extra={"classification": classification["classification"]},
        )

    return response(
        success=False,
        task=task if isinstance(task, str) else None,
        confidence=0.0,
        errors=[
            {
                "code": "unsupported_task",
                "message": "Unsupported AI engine task.",
            }
        ],
    )


def main() -> int:
    raw_input = sys.stdin.read()

    try:
        payload = json.loads(raw_input or "{}")
    except json.JSONDecodeError:
        print(
            json.dumps(
                response(
                    success=False,
                    task=None,
                    confidence=0.0,
                    errors=[
                        {
                            "code": "invalid_json",
                            "message": "Invalid JSON input.",
                        }
                    ],
                )
            )
        )

        return 0

    if not isinstance(payload, dict):
        result = response(
            success=False,
            task=None,
            confidence=0.0,
            errors=[
                {
                    "code": "invalid_payload",
                    "message": "JSON input must be an object.",
                }
            ],
        )
    else:
        result = handle(payload)

    print(json.dumps(result))

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
