# simple-ots
Simple "OneTimeSecret" Solution.

## Usage
This image will just expose a form to store "one time" secrets and will produce a URL to read them.

You can se the project's `docker-compose.yml` to run it locally. To use the already-built Docker Image, just run `docker pull roura/simple-ots`.

### Limiting Access
If you want to limit who can **create** secrets, you can set the `AUTH_IPS` environment variable to a comma-separated set of IPs.

## Some screenshots
![](https://github.com/rouralberto/simple-ots/assets/7732989/0bb84ae0-88ee-4fe9-a634-e104e8944c06)
