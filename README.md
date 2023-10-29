# simple-its
Simple "OneTimeSecret" Solution.

## Usage
This image will just expose a form to store "one time" secrets and will produce a URL to read them.

### Limiting Access
If you want to limit who can create secrets, you can set the `AUTH_IPS` environment variable to a comma-separated set of IPs.
