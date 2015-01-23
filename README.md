# imgin: Image manipulation directory

## Install

```sh
$ composer -sdev create-project k1low/imgin ./app/webroot/img
...

Writing lock file
Generating autoload files
Do you want to remove the existing VCS (.git, .svn..) history? [Y,n]? Y
```

## Usage

### Original image access

/img/foo/bar/large.jpg

### Resized image access

/img/100x120/foo/bar/large.jpg

### Clear cache image

```sh
$ php app/webroot/img/imgin.php clearcache /path/to/app/webroot/img/foo/bar/large.jpg
```

## Requirement

- PHP >=5.3
- GD
- mod_rewrite
- [Imagine](http://imagine.readthedocs.org/en/latest/)
- [Commando](https://github.com/nategood/commando)

## License

under MIT License
