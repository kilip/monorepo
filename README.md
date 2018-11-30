monorepo
====
:tada: One Ring to Rule Them All :tada:

![Travis (.org)](https://img.shields.io/travis/kilip/monorepo.svg?style=popout-square)
![Codecov](https://img.shields.io/codecov/c/github/kilip/monorepo.svg?style=popout-square)
![Scrutinizer](https://img.shields.io/scrutinizer/g/kilip/monorepo.svg?style=popout-square)
[![Download monorepo](https://img.shields.io/sourceforge/dt/monorepo.svg?style=popout-square)](https://sourceforge.net/projects/monorepo/files/monorepo.phar/download)

About
====
Monorepo is a tool to manage your mono or multi repository.

Features
====
*  Automatically synchronize your child repository into root repository or vice versa
*  A cool console style

Installation
====
Choose and download your phar based on your operating system in [here](https://sourceforge.net/projects/monorepo/nightly)

Usage
====
Create your json file
```json
// path/to/project
[
  {
      "name": "project1",
      "remote_url": "git@github.com:project1/root",
      "prefixes": [
           {
              "key": "git@githusrc/foo",
              "target": "git@github.com:project1/foo.git"      
           },
           {
              "key": "src/bar",
              "target": "git@github.com:project1/bar.git"      
           }
      ]
  },
  {
      "name": "project2",
      "remote_url": "git@github.com:project2/root",
      "prefixes": [
          {
              "key": "src/hello",
              "target": "git@github.com:project2/hello.git"      
          },
          {
              "key": "src/world",
              "target": "git@github.com:project2/world.git"      
          }
      ]
  }
]
``` 