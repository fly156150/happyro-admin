FROM --platform=$BUILDPLATFORM node:22-bookworm AS builder
WORKDIR /build
COPY repos/happyro-admin/package*.json ./
COPY repos/happyro-admin/.npmrc ./
RUN npm ci --ignore-scripts
COPY repos/happyro-admin/ ./
RUN npx max setup && npm run build

FROM nginx:stable-alpine-slim@sha256:dede8895648b461178c3bc604f7eed4423efe5220b2c4d839f5eb3d34ac3f8f7
COPY repos/happyro-admin/deploy/docker/nginx.conf /etc/nginx/nginx.conf
COPY --from=builder /build/dist/ /usr/share/nginx/html/
USER nginx
EXPOSE 8080
ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]
