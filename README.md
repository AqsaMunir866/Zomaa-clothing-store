# Zomaa Clothing Store

A full-stack e-commerce application for an online clothing store, built with Laravel, Vue.js, and MySQL. The project is designed to support product browsing, store management, customer orders, and payment workflows.

## Features

- Product and category management
- Customer and order management
- Store banner management
- Store settings management
- Payment method configuration
- Payment proof support
- Laravel REST API integration
- Vue.js administration interface
- MySQL database
- Docker-based development environment

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Backend programming |
| Laravel | Backend framework and REST APIs |
| Vue.js | Frontend and administration interface |
| JavaScript | Frontend functionality |
| MySQL | Database |
| Tailwind CSS | UI styling |
| Docker | Development environment |
| Composer | PHP dependency management |
| npm | JavaScript dependency management |
| Git and GitHub | Version control |

## Project Structure

The repository contains the Laravel application, Vue.js frontend components, database migrations and seeders, API routes, Docker configuration, and supporting documentation.

## Getting Started

### Prerequisites

Install the following tools before starting:

- Git
- PHP and Composer, if running Laravel outside Docker
- Node.js and npm
- MySQL, if running without Docker
- Docker Desktop, if using the Docker setup

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/AqsaMunir866/Zomaa-clothing-store.git
   cd Zomaa-clothing-store
   ```

2. Configure the environment variables using the appropriate example environment file. Do not commit real credentials or secret keys.

3. Install the PHP dependencies:

   ```bash
   composer install
   ```

4. Configure the database connection in your local environment file.

5. Generate the Laravel application key if required:

   ```bash
   php artisan key:generate
   ```

6. Run database migrations when your database is configured:

   ```bash
   php artisan migrate
   ```

7. Install JavaScript dependencies:

   ```bash
   npm install
   ```

8. Start the frontend development server using the scripts defined in `package.json`:

   ```bash
   npm run dev
   ```

9. Start Laravel in a separate terminal when running outside Docker:

   ```bash
   php artisan serve
   ```

### Docker Setup

The repository includes Docker configuration for containerized development. Review `docker-compose.yml`, the Dockerfile, and the related configuration files before starting the containers.

Use the environment variables and startup commands required by the current Docker configuration.

## Configuration and Security

- Keep `.env` files containing local credentials out of Git.
- Use environment variables for database passwords, API keys, and other secrets.
- Configure production URLs and deployment settings only when a hosting environment has been selected.
- Never use sample credentials as production administrator credentials.

## Project Status

Under development. Features, setup instructions, and deployment configuration may change as development progresses.

## Author

**Aqsa Munir**

- GitHub: https://github.com/AqsaMunir866
- Portfolio: https://aqsa-rana.netlify.app
- LinkedIn: https://www.linkedin.com/in/aqsa-munir-393165243/
