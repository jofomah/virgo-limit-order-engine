#!/bin/bash

echo "Setting up environment..."

cd backend

if [ ! -f ".env" ]; then
    cp .env.example .env
    echo ".env file created."
else
    echo ".env already exists, skipping..."
fi

echo "Backend Environment setup complete!"
