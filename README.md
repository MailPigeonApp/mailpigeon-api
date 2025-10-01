# MailPigeon API

A lightweight, flexible form submission backend API built with Leaf PHP.

**Note:** This is the submission API backend for MailPigeon. The main web application backend is maintained in a separate repository.

## Features

- **Dynamic Form Validation** - Configure fields and validation rules per project
- **Type Checking** - Automatic validation for strings, arrays, files, and other data types
- **File Uploads** - AWS S3 integration for secure file storage
- **API Key Authentication** - Secure Bearer token authentication
- **Integrations** - Built-in support for Telegram notifications (more coming soon)
- **Auto-incrementing Submissions** - Track submission counts with soft-delete support
- **CORS Enabled** - Ready for frontend integration

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL database
- AWS S3 account (for file uploads)

### Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd mailpigeon-api
```

2. Install dependencies:

```bash
composer install
```

3. Configure environment variables:

Create a `.env` file in the root directory:

```env
DB_TYPE=pgsql
DB_HOST=your-database-host
DB_USERNAME=your-username
DB_PASSWORD=your-password
DB_NAME=your-database-name

AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET=your-aws-secret-key
AWS_S3_BUCKET=your-s3-bucket-name
```

4. Update `config/db.php` for local development:

Uncomment lines 10-12 and comment out lines 5-8 to enable local `.env` file loading.

5. Run the application using your preferred PHP server or:

```bash
php -S localhost:8080
```

## API Endpoints

### Submit Form Data

```http
POST /api/v1/submit
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "fieldName1": "value1",
  "fieldName2": "value2"
}
```

**Response:**

```json
{
  "message": "Submission successful"
}
```

**Error Response:**

```json
{
  "data": {
    "message": "Submission failed",
    "error": ["Field 'email' is required", "Field 'age' is not of type integer"]
  },
  "status": {
    "code": 400,
    "message": "Bad Request"
  }
}
```

### Upload File

```http
POST /api/v1/upload
Authorization: Bearer YOUR_API_KEY
Content-Type: multipart/form-data

file: [binary file data]
```

**Response:**

```json
{
  "url": "https://your-bucket.s3.amazonaws.com/filename.jpg"
}
```

## Database Schema

The application expects the following PostgreSQL tables:

### `apikey`

- `key` - VARCHAR (Primary Key)
- `projectId` - UUID/VARCHAR (Foreign Key)
- `userId` - UUID/VARCHAR (Foreign Key)

### `project`

- `id` - UUID/VARCHAR (Primary Key)
- `name` - VARCHAR
- `fields` - JSONB - Stores field configuration
- `deleted_count` - INTEGER - Tracks soft-deleted submissions
- `active_integrations` - TEXT[] - PostgreSQL array of active integration names

### `submission`

- `increment` - INTEGER - Auto-incrementing submission number
- `projectId` - UUID/VARCHAR (Foreign Key)
- `userId` - UUID/VARCHAR (Foreign Key)
- `data` - JSONB - Submission data

### `integrations`

- `projectId` - UUID/VARCHAR (Foreign Key)
- `type` - VARCHAR - Integration type (e.g., "telegram")
- `data` - JSONB - Integration configuration

## Field Configuration

Fields are configured as JSON in the `project.fields` column:

```json
[
  {
    "name": "email",
    "type": "string",
    "required": true
  },
  {
    "name": "age",
    "type": "integer",
    "required": false
  },
  {
    "name": "tags",
    "type": "array",
    "required": false
  },
  {
    "name": "attachment",
    "type": "file",
    "required": false
  }
]
```

## Integrations

### Telegram

To enable Telegram notifications:

1. Add "telegram" to the `project.active_integrations` array
2. Create an integration record in the `integrations` table:

```json
{
  "type": "telegram",
  "data": {
    "chatId": "your-telegram-chat-id"
  }
}
```

When a form is submitted, a notification will be sent to the configured Telegram chat.

## Deployment

### Fly.io

The application is configured for deployment on Fly.io:

1. Install the Fly CLI: https://fly.io/docs/hands-on/install-flyctl/

2. Login to Fly:

```bash
fly auth login
```

3. Deploy:

```bash
fly deploy
```

4. Set environment secrets:

```bash
fly secrets set DB_HOST=your-host DB_USERNAME=your-user DB_PASSWORD=your-pass
fly secrets set AWS_ACCESS_KEY_ID=your-key AWS_SECRET=your-secret
```

5. View logs:

```bash
fly logs
```

## Authentication

All API endpoints require authentication using Bearer tokens:

```http
Authorization: Bearer YOUR_API_KEY
```

API keys are validated against the `apikey` table in the database.

## Error Codes

- `200` - Success
- `201` - Submission successful
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (invalid API key)
- `404` - Not Found (project not found)
- `406` - Not Acceptable (missing required configuration)
- `500` - Internal Server Error
- `501` - Not Implemented

## Built With

- [Leaf PHP](https://leafphp.dev/) - Lightweight PHP micro-framework
- [AWS SDK for PHP](https://aws.amazon.com/sdk-for-php/) - AWS S3 integration
- [phpdotenv](https://github.com/vlucas/phpdotenv) - Environment variable management

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to contribute to this project.
