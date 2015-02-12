# imgin: Image manipulation directory

## Install

```sh
$ composer -sdev create-project k1low/imgin ./app/webroot/img
...

Writing lock file
Generating autoload files
Do you want to remove the existing VCS (.git, .svn..) history? [Y,n]? Y
```

## Usage (File)

### Original image access

/img/foo/bar/large.jpg

### Resized image access

/img/100x120/foo/bar/large.jpg

### Clear cache image

```sh
$ php app/webroot/img/imgin.php clearcache /path/to/app/webroot/img/foo/bar/large.jpg
```

## Usage (S3)

### Original image access

**TODO**

### Resized image access

/img/100x120/foo/bar/large.jpg

### Clear cache image

**TODO**

## Requirement

- PHP >=5.3
- GD
- mod_rewrite
- [Imagine](http://imagine.readthedocs.org/en/latest/)
- [Commando](https://github.com/nategood/commando)
- AWS SDK for PHP

## License

under MIT License
