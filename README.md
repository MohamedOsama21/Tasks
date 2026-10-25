# Task Manager: Multi-tier Containerized Application with Monitoring

## Project Overview

This project runs a task management application (register, login, add and
delete tasks) as a multi-container stack on Docker Compose.

The goal is **DevOps**, not application logic: running an application so that
it is secure, resilient, scalable and observable. The application code is
intentionally simple; the focus is the infrastructure around it.

## Architecture

```text
                    Browser
                       |
                       | HTTP :8081
                       v
              Nginx Reverse Proxy
                /             \
          "/"  /               \  "/api/"
              v                 v
        Frontend (Nginx)    Backend x3 (PHP-FPM)
                              /          \
                             v            v
                          MySQL 8.0    Redis 7

   cAdvisor --> Prometheus --> Grafana :3000   (monitoring)
```

## Environment

- Virtualization: VMware Workstation
- Operating system: Rocky Linux virtual machine
- Orchestration: Docker Compose
- Containers: 10 services across 3 isolated networks

| Layer | Technology | Role |
|---|---|---|
| Reverse proxy | Nginx | Single entry point, request routing |
| Frontend | HTML/JS served by Nginx | User interface |
| Backend | PHP 8.2-FPM (3 replicas) | Application logic and API |
| Database | MySQL 8.0 | Persistent storage |
| Cache / Sessions | Redis 7 | Task cache and shared sessions |
| Monitoring | cAdvisor, Prometheus, Grafana | Metrics, storage, dashboards |

## How It Works

1. The browser sends a request to the Nginx reverse proxy on port 8081.
2. Requests for `/` are proxied to the frontend container (static HTML).
3. Requests for `/api/` are forwarded to the PHP-FPM backend over FastCGI.
4. Nginx re-resolves the `backend` DNS name, so requests are balanced across
   the 3 backend replicas.
5. The backend reads task lists from Redis when cached, otherwise from MySQL.
6. User sessions are stored in Redis, so any replica can serve any user
   (stateless backend).
7. cAdvisor measures every container, Prometheus stores the metrics, and
   Grafana displays them.

## Getting Started

### Prerequisites

- Docker Engine and Docker Compose v2
- Ports `8081` (application) and `3000` (Grafana) available

### 1. Clone the repository

```bash
git clone https://github.com/MohamedOsama21/Tasks.git
cd Tasks
```

### 2. Create the environment file and secrets

Real credentials are never committed. Copy the examples and set your own values:

```bash
cp .env.example .env
cp -r secrets.example secrets
```

Edit the two files in `secrets/` and replace the placeholder passwords.
The files must contain the password only, with no trailing newline:

```bash
printf '%s' 'YourRootPassword' > secrets/db_root_password.txt
printf '%s' 'YourUserPassword' > secrets/db_password.txt
chmod 644 secrets/db_password.txt
```

`db_password.txt` must be readable by the `www-data` user inside the backend
container, which is why it uses mode `644`.

### 3. Start the stack

```bash
docker compose up -d --build
docker compose ps
```

All containers should be `Up`, and `db` and `redis` should be `(healthy)`.

### 4. Open the services

| Service | URL |
|---|---|
| Application | http://localhost:8081 |
| Grafana | http://localhost:3000 (default login `admin` / `admin`) |

In Grafana, add a Prometheus data source with the URL `http://prometheus:9090`,
then import dashboard ID `14282`.

## Verifying the Setup

Check that Nginx routes to the right containers:

```bash
curl -s http://localhost:8081/ | head -3
curl -s -X POST -d "username=x&password=x" http://localhost:8081/api/login.php
```

The first returns HTML (frontend), the second returns JSON (backend).

Check network isolation (Nginx must not reach the database):

```bash
docker compose exec nginx getent hosts db        # no output
docker compose exec backend getent hosts db      # returns an IP
```

Check that sessions are stored in Redis, not in the backend containers:

```bash
docker compose exec redis redis-cli keys 'PHPREDIS_SESSION*'
```

Check the secret is mounted as a file and not exposed as a variable:

```bash
docker inspect task-manager-backend-1 | grep -i password
```

Check resource limits:

```bash
docker stats --no-stream
```
