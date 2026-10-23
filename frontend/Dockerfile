# syntax=docker/dockerfile:1
#
# Frontend ChainFolio — multi-stage build:
#   1. build   → Node: npm ci + npm run build (menghasilkan folder dist/)
#   2. runtime → nginx (non-root) yang menyajikan dist/ dan mem-proxy /api ke backend
#
# Image akhir TIDAK berisi Node, node_modules, maupun source code — hanya file statis.

# Tag versi eksplisit (bukan "latest").
ARG NODE_IMAGE=node:22.22.0-alpine
ARG NGINX_IMAGE=nginxinc/nginx-unprivileged:1.30-alpine

# ── 1. build ─────────────────────────────────────────────────────────────────
FROM ${NODE_IMAGE} AS build

WORKDIR /app

# package*.json disalin dulu → layer `npm ci` tetap ter-cache selama dependency tetap.
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .

# Nilai VITE_ bersifat PUBLIK (ikut ter-bundle ke JavaScript). Jangan isi rahasia.
ARG VITE_API_BASE_URL=/api
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}

RUN npm run build

# ── 2. runtime ───────────────────────────────────────────────────────────────
FROM ${NGINX_IMAGE} AS runtime

COPY nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

# nginx-unprivileged berjalan sebagai user "nginx" (UID 101) di port 8080.
EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=3s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/healthz >/dev/null || exit 1
