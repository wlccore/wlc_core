Unified response format:
=

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "$id": "http://wlc_core.com/response.schema.json",
  "title": "Response",
  "description": "The Unified API Response schema",
  "type": "object",
  "properties": {
    "code": {
      "description": "The code of result",
      "type": "integer"
    },
    "status": {
      "description": "The common result of the success of the operation",
      "type": "string",
      "enum": [
        "success",
        "error"
      ]
    },
    "data": {
      "description": "The payload",
      "type": "object"
    },
    "errors": {
      "description": "The errors",
      "type": "array",
      "items": {
        "type": "string"
      }
    }
  },
  "required": [
    "code",
    "status"
  ]
}
```