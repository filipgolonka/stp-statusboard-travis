# Travis Widget

## Config

```
-
  id: travisbranch
  provider: \StpBoard\Travis\TravisControllerProvider
  refresh: 30
  width: 4
  params:
    travis_url: https://api.travis-ci.org
    github_token: github-token
    repo: owner/repo-name
    branch: master
    type: branch
-
  id: travispullrequests
  provider: \StpBoard\Travis\TravisControllerProvider
  refresh: 30
  width: 12
  params:
    travis_url: https://api.travis-ci.org
    github_token: github-token
    repo: owner/repo-name
    type: pullrequests
    limit: 3
```
