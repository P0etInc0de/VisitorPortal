group "sso-e2e" {
  targets = ["sso-web", "sso-smoke"]
}

target "sso-web" {
  context = "."
  dockerfile = "docker/app/Dockerfile"
  target = "sso-test"
  tags = ["visitorportal-sso-web:test"]
  output = ["type=docker"]
  cache-from = ["type=gha,scope=sso-web"]
  cache-to = ["type=gha,mode=max,scope=sso-web"]
}

target "sso-smoke" {
  context = "."
  dockerfile = "docker/sso-test/Dockerfile.smoke"
  tags = ["visitorportal-sso-smoke:test"]
  output = ["type=docker"]
  cache-from = ["type=gha,scope=sso-smoke"]
  cache-to = ["type=gha,mode=max,scope=sso-smoke"]
}
