# --- Base Node Image ---
FROM node:20-alpine AS base

# Create working directory
WORKDIR /app

# Install dependencies early to leverage Docker caching
COPY package.json package-lock.json ./
RUN npm install

# Copy remaining source code
COPY . .

# Default command for development
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
