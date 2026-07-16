FROM node:latest

# Deps de base
RUN apt-get update \
 && apt-get install -y curl ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# Docker CLI
RUN curl -fsSL https://get.docker.com | sh

# Docker Compose v2 plugin
RUN mkdir -p /usr/local/lib/docker/cli-plugins \
 && curl -SL https://github.com/docker/compose/releases/download/v2.25.0/docker-compose-linux-x86_64 \
    -o /usr/local/lib/docker/cli-plugins/docker-compose \
 && chmod +x /usr/local/lib/docker/cli-plugins/docker-compose

# Gemini
RUN npm install -g @google/gemini-cli

WORKDIR /home/node/app
