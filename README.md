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

Update
====
```sh
$ php mr.phar selfupdate
```

Usage
====
Create your configuration file
```json
// path/to/project
[
  {
      "name": "foo-bar",
      "remote_url": "git@github.com:foo-bar/root",
      "prefixes": [
           {
              "key": "src/foo",
              "target": "git@github.com:foo-bar/foo.git"      
           },
           {
              "key": "src/bar",
              "target": "git@github.com:foo-bar/bar.git"      
           }
      ]
  },
  {
      "name": "hello-world",
      "remote_url": "git@github.com:hello-world/root",
      "prefixes": [
          {
              "key": "src/hello",
              "target": "git@github.com:hello-world/hello.git"      
          },
          {
              "key": "src/world",
              "target": "git@github.com:hello-world/world.git"      
          }
      ]
  }
]
``` 