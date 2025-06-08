FROM php:8.2-cli
WORKDIR /app
COPY . .
EXPOSE $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT index.php"]
