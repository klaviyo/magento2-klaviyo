# Internal

This document is for internal use only. Anyone can review this document, but
only Klaviyo employees will have access to the specified links.

## CI/CD Pipelines

The CI/CD Pipelines in this repository follow the
[IES standard release strategy](https://klaviyo.atlassian.net/l/cp/NUbUCVHo).
Please review this document before cutting a release.

## Making updates
1) Follow Klaviyo's standard process of making a pull request and getting it reviewed before merging.
2) Update CHANGELOG.md. **NOTE:** Please use the [Changelogger](https://pypi.org/project/changelogged/) cli tool to manage versioned file upgrades. Details on formatting the changelog (including categorizing changes) can be found here: [keepachangelog.com](https://keepachangelog.com/en/1.0.0/)
    1) If this is a change that will not immediately get sent along to Magento i.e. not a version update:
        1) Add any changes under the [`[Unreleased]`](https://github.com/klaviyo/magento2-klaviyo/compare/1.0.1...HEAD) section. This will be a comparision of the most recent commits to the latest tagged version.
    2) If this is a version update:
        1) Make sure to increment the version in two places:
            1) module.xml
            2) composer.json
        2) Add a new version between `[Unreleased]` and the most recent version. Include the incremented version number following [semantic versioning](https://semver.org/spec/v2.0.0.html) practices and the date. Add your changes under this version.
        3) Move any unreleased changes into your version update under the appropriate categories.
        4) Update the `[Unreleased]` link to point from your new version to HEAD e.g. if you're updating to version 1.0.2 you'd update the link from `1.0.1...HEAD` to `1.0.2...HEAD`.
        5) Add a link to your new version. The tag won't yet exist but you can create a link to the tag you will create shortly. Follow the pattern of previous links.
3) Upon approval merge your changes into master.
    1) If this is a version update:
        1) Checkout the master branch locally, make sure to pull down any changes that were just merged.
        2) Use `git log` to find the merge commit's checksum.
        3) Tag this commit with the version you just incremented: `git tag -a {version} {commit hash}` or just use `git tag -a {version}`.
        4) Push the tag to the remote repository: `git push origin 1.0.1` replacing with the version you've just tagged.

## Releasing a new version
1) Submit the new version for approval on the Magento Marketplace using partner account
2) [Update Packagust / Composer](https://klaviyo.atlassian.net/wiki/spaces/EN/pages/1530593327/Contributing+to+and+Updating+the+Magento+2+Extension#Update-Packagist%2FComposer) using the linked internal documentation
