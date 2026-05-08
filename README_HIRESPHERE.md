# HireSphere – SE6020 Cloud Computing Assignment

Cloud-native technical interview simulation platform (Laravel API + React + MySQL auth).

## Mode A (No AWS)

This repository is configured for **Mode A**: local/cloud-agnostic deployment without AWS dependencies.

- Authentication: Laravel API token auth (no Cognito)
- Frontend hosting: Vite/Nginx (no Amplify required)
- Database: MySQL
- Deployment: Docker Compose and Kubernetes manifests
- Infrastructure docs/scripts: cloud-agnostic local setup

## Quick start

### Backend (Laravel API)

```bash
cp .env.example .env
php artisan key:generate
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD (e.g. MySQL)
php artisan migrate
php artisan serve
```

API: http://localhost:8000  
Health: http://localhost:8000/api/health

### Frontend (React SPA)

Run:

```bash
cd frontend
npm install
npm run dev
```

Open the Vite URL shown in terminal (normally http://localhost:5173).

### Docker (API + MySQL)

```bash
docker compose up -d
# API: http://localhost:8000
```

### Kubernetes

1. Build image: `docker build -t hiresphere-api:latest .`
2. Create secret `hiresphere-db` with DB host, database, username, password.
3. Apply microservice deployments: `kubectl apply -f kubernetes/microservices-deployment.yaml`
4. Apply ingress gateway: `kubectl apply -f kubernetes/api-ingress.yaml`

#### Microservice-aligned API domains

- `users-service` routes: `/api/users/*` (profiles, interviewer search)
- `scheduling-service` routes: `/api/scheduling/*` (bookings, availability, interviews, evaluations)
- `interaction-service` routes: `/api/interaction/*` (submissions, messaging)
- `payments-service` routes: `/api/payments/*` and `/api/interaction/payments/*` (mock payment integration flow)
- `realtime-service` routes: `/api/realtime/*` and `/api/interaction/realtime/*` (WebRTC signaling endpoints)
- Backward-compatible legacy routes under `/api/*` remain enabled for current frontend.

### Local auth endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me` (requires bearer token)
- `POST /api/auth/logout` (requires bearer token)

## Assignment deliverables

| Item | Location |
|------|----------|
| Solution report & architecture | `docs/SOLUTION_REPORT.md` |
| Microservices (Laravel API) | `app/`, `routes/api.php` |
| Web interface (React + local auth integration) | `frontend/` |
| Containerised API | `Dockerfile`, `docker-compose.yml` |
| Kubernetes deployment (3 domain services + ingress gateway) | `kubernetes/` |
| Mode A (No AWS) runbook | `docs/MODE_A_NO_AWS.md` |

## API overview

- `GET /api/health` – health check
- `GET/PUT /api/profiles/me` – current user profile (auth)
- `GET /api/interviewers` – search interviewers (query: domain, interview_type, experience_level)
- `GET/POST /api/bookings` – list/create bookings (auth)
- `GET/POST /api/availability` – interviewer availability (auth)
- `GET /api/interviews`, `GET /api/interviews/{id}` – interview history (auth)
- `POST /api/interviews/{id}/evaluation` – submit evaluation (auth, interviewer)
- `GET/POST /api/submissions` – coding submissions (auth)
- `GET/POST /api/conversations`, `GET/POST /api/conversations/{id}/messages` – messaging (auth)
- `GET /api/payments`, `POST /api/payments/initiate`, `PATCH /api/payments/{id}/status` – payment lifecycle (auth)
- `POST /api/realtime/sessions`, `POST /api/realtime/sessions/{id}/offer|answer|ice-candidates|end` – WebRTC signaling session APIs (auth)

Auth: send local API token in header: `Authorization: Bearer <token>`.
