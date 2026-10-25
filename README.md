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
