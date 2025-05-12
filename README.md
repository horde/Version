# horde/version

Create, parse, increment and compare strict SemVer V2 version tags and other common semantic versioning formats found out in the wild

## Motivation

SemVer V2 is a useful attempt to standardize semantic versioning. However semantic versioning itself predates it.
Thus many semver-like formats exist and there is a real world need to handle them.

Common deviations from SemVer format:
- Treating a prerelease version "dev" as inferior to alpha. SemVer V2 says alphabetic prepatch components are compared by string, thus alpha < beta < dev < rc
- Prerelease versions without a hyphen after the patch version number are common
- Version strings with only major.minor sometimes need to be compared to specific versions, especially as part of version requirement strings
- SemVer v2 splits components of prerelease versions by dots but out there, "alpha4" is much more common than "alpha.4"
- Prefixes like v before the actual version are treated as not being part of SemVer V2 but strings containing these are common.
- Some inhouse schemes support sub-patch versions (Major.Minor.Patch.SubPatch) to denote rebuilds of equivalent source code versions on changed tool chains

## Origin

A similar implementation existed in

- horde/components Developer CLI

But it was not practical for other use cases.
