# Nadi Qurban OSI — API v1 & Webhooks

Base URL: `https://<domain>/api/v1` · JSON only · UTF-8.

## Authentication

Create a key in **Tetapan › Integrasi API › Jana Kunci API**. The key is shown **once**; store it securely.

```http
Authorization: Bearer nq_12|Xy8…
Accept: application/json
```

| Ability | Grants |
|---------|--------|
| `read`  | `GET /products`, `GET /orders/{reference}`, `GET /orders/{reference}/status` |
| `write` | `POST /orders` |

Revoked or unknown keys → `401`. Missing ability → `403`.

## Rate limit

120 requests per minute **per key**. Exceeding it returns `429` with `Retry-After`. Usage is shown on the Integrasi API page.

## Errors

```json
{ "message": "The customer.phone field is required.", "errors": { "customer.phone": ["…"] } }
```

| Status | Meaning |
|--------|---------|
| 401 | Missing / invalid / revoked key |
| 403 | Key lacks the required ability |
| 404 | Order or product not found |
| 422 | Validation failed (body lists the fields) |
| 429 | Rate limit exceeded |

---

## OpenAPI 3.1

```yaml
openapi: 3.1.0
info:
  title: Nadi Qurban OSI API
  version: "1.0"
servers:
  - url: https://{domain}/api/v1
    variables: { domain: { default: osi.nadiqurban.com } }
security:
  - bearerAuth: []
components:
  securitySchemes:
    bearerAuth: { type: http, scheme: bearer }
  schemas:
    Money:
      type: object
      properties:
        total_sen: { type: integer, example: 245000 }
        discount_sen: { type: integer, example: 0 }
        formatted: { type: string, example: "RM 2,450" }
    Order:
      type: object
      properties:
        order_no: { type: string, example: NQ-QB-LE-001248 }
        tracking_no: { type: string, example: NQT-2027-001248 }
        service: { type: string, enum: [qurban, aqiqah, dam, nazar] }
        animal: { type: string, enum: [lembu, kambing, unta] }
        package: { type: string, example: Delima }
        product: { type: string }
        country: { type: string, example: Uganda }
        quantity: { type: integer, example: 1 }
        year: { type: integer, example: 2027 }
        implementation_date: { type: [string, "null"], format: date }
        amount: { $ref: "#/components/schemas/Money" }
        payment:
          type: object
          properties:
            method: { type: string, enum: [fpx, cek, pindahan_bank] }
            status: { type: [string, "null"], enum: [menunggu, disahkan, ditolak, null] }
        status: { type: string, enum: [draf, menunggu_bayaran, diterima, dalam_proses, selesai, dibatalkan] }
        stage: { type: string, example: payment_verified }
        customer:
          type: object
          properties: { name: { type: string }, phone: { type: string }, email: { type: [string, "null"] } }
        participants: { type: array, items: { type: string } }
        tracking_url: { type: string, format: uri }
        created_at: { type: string, format: date-time }
paths:
  /products:
    get:
      summary: Active products
      parameters:
        - { name: service, in: query, schema: { type: string, enum: [qurban, aqiqah, dam, nazar] } }
        - { name: country, in: query, description: ISO-3166 alpha-2, schema: { type: string, example: UG } }
      responses:
        "200":
          description: OK
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      type: object
                      properties:
                        id: { type: integer }
                        code: { type: string }
                        name: { type: string }
                        service: { type: string }
                        animal: { type: string }
                        package: { type: string }
                        country: { type: object, properties: { name: { type: string }, iso2: { type: string } } }
                        price: { type: object, properties: { amount_sen: { type: integer }, formatted: { type: string } } }
                        in_stock: { type: boolean }
  /orders/{reference}:
    get:
      summary: Order by order no. or tracking no.
      parameters:
        - { name: reference, in: path, required: true, schema: { type: string, example: NQ-QB-LE-001248 } }
      responses:
        "200": { description: OK, content: { application/json: { schema: { type: object, properties: { data: { $ref: "#/components/schemas/Order" } } } } } }
        "404": { description: Not found }
  /orders/{reference}/status:
    get:
      summary: Lightweight status for polling
      parameters:
        - { name: reference, in: path, required: true, schema: { type: string } }
      responses:
        "200":
          description: OK
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      order_no: { type: string }
                      tracking_no: { type: string }
                      status: { type: object, properties: { code: { type: string }, label: { type: string } } }
                      stage: { type: object, properties: { code: { type: string }, label: { type: string }, step: { type: integer }, of: { type: integer } } }
                      tracking_url: { type: string }
                      updated_at: { type: string, format: date-time }
  /orders:
    post:
      summary: Create an order (ability "write")
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [customer, product_id, quantity, payment_method]
              properties:
                customer:
                  type: object
                  required: [name, phone, state]
                  properties:
                    name: { type: string, maxLength: 150 }
                    phone: { type: string, example: "012-345 6789" }
                    email: { type: string, format: email }
                    address: { type: string }
                    postcode: { type: string, pattern: "^[0-9]{5}$" }
                    city: { type: string }
                    state: { type: string, example: Selangor, description: One of the 16 Malaysian states / territories }
                product_id: { type: integer }
                quantity: { type: integer, minimum: 1, maximum: 700 }
                year: { type: integer, description: Defaults to the current season }
                implementation_date: { type: string, format: date }
                promo_code: { type: string }
                payment_method: { type: string, enum: [fpx, cek, pindahan_bank] }
                participants: { type: array, items: { type: string } }
                notes: { type: string, maxLength: 500 }
      responses:
        "201": { description: Created, content: { application/json: { schema: { type: object, properties: { data: { $ref: "#/components/schemas/Order" } } } } } }
        "422": { description: Validation error (stock, promo code, fields) }
```

### Example

```bash
curl -X POST https://osi.nadiqurban.com/api/v1/orders \
  -H "Authorization: Bearer $NQ_KEY" -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"customer":{"name":"Ahmad Zaki","phone":"0123456789","state":"Selangor"},"product_id":3,"quantity":1,"payment_method":"fpx","participants":["Ahmad Zaki"]}'
```

---

## Outgoing webhooks

Manage endpoints in **Tetapan › Webhooks**. Each event is POSTed as JSON:

```json
{
  "id": "6f1c…",                     // delivery id (idempotency key)
  "event": "payment.confirmed",
  "created_at": "2027-06-12T14:20:00+08:00",
  "data": { "order": { …same shape as GET /orders/{reference}… } }
}
```

| Event | Fired when |
|-------|-----------|
| `payment.confirmed` | Payment verified (stage *Bayaran Disahkan*) |
| `report.verified` | HQ verifies the execution report |
| `awb.generated` | AWB / airway bill generated |
| `order.completed` | Order completed |
| `ping` | "Uji" button on an endpoint |

**Signature.** Header `X-NQ-Signature` = `hex(HMAC-SHA256(raw_body, signing_secret))`; header `X-NQ-Timestamp` = Unix time. Verify before trusting the payload:

```php
$expected = hash_hmac('sha256', $request->getContent(), $secret);
abort_unless(hash_equals($expected, $request->header('X-NQ-Signature')), 401);
```

**Retries.** A non-2xx response or timeout (10 s) is retried up to 3 attempts with exponential backoff. Every attempt is visible in *Log Penghantaran*. Respond `2xx` quickly and process asynchronously; use `id` to ignore duplicates.
