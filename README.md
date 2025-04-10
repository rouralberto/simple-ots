# Simple-OTS

A lightweight and secure "One Time Secret" solution for sharing sensitive information safely.

![](https://github.com/rouralberto/simple-ots/assets/7732989/0bb84ae0-88ee-4fe9-a634-e104e8944c06)

## Features

- **One-Time Access**: Secrets are automatically deleted after being viewed
- **Expiration Times**: Set secrets to expire after 1 hour, 1 day, or 1 week
- **Simple Interface**: Clean, dark-themed Bootstrap UI
- **Copy Functionality**: Easy copy buttons for both secret links and content
- **Access Control**: Optional IP-based authentication for creating secrets
- **Docker Ready**: Quick deployment with Docker and Docker Compose
- **SQLite Backend**: Lightweight database with no additional dependencies

## Usage

### Quick Start

The easiest way to get started is using Docker:

```bash
# Pull the image
docker pull roura/simple-ots

# Run the container
docker run -p 80:80 roura/simple-ots
```

Or using the provided Docker Compose file:

```bash
# Clone the repository
git clone https://github.com/rouralberto/simple-ots.git
cd simple-ots

# Start the service
docker-compose up -d
```

Then navigate to `http://localhost` in your browser.

### Creating a Secret

1. Enter your sensitive information in the "Secret" field
2. Select an expiration time (1 hour, 1 day, or 1 week)
3. Click "Create Secret"
4. Share the generated URL with the intended recipient

### Viewing a Secret

1. Open the shared URL
2. Click the link to view the secret
3. The secret will be displayed only once and then permanently deleted
4. Use the copy button to safely copy the content to your clipboard

## Configuration

### Limiting Access

To restrict who can create secrets, set the `AUTH_IPS` environment variable to a comma-separated list of allowed IP addresses:

```yaml
environment:
  AUTH_IPS: 1.1.1.1,8.8.8.8
```

## Development

To set up a development environment:

```bash
# Clone the repository
git clone https://github.com/rouralberto/simple-ots.git
cd simple-ots

# Edit docker-compose.yml to mount your local directory
# Start the container
docker-compose up
```

## License

This project is licensed under the terms of the LICENSE file included in the repository.

## Contributing

Contributions are welcome! Feel free to open issues or submit pull requests.
