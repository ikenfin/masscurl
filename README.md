# masscurl

PHP script that filter and output curl data. It can be used for information gathering about site urls.

## Options

**-r** - urls file to read from (by default - **STDIN**)

**-w** - output to file (by default - **STDOUT**)

**-m** - mode(see above)

**-f** - filter (see above)

**-v** - verbose mode


### mode:

Mode is output mode. There is number of options:

f - flat mode (just print urls)
ss - prints status
ri - request info
rsi - response info
rc - redirect info
t - time info

### filter:

Filters may be used to filter output data. Options:

s - urls with exact status codes
f - urls with 200 status codes
nf- urls with 404 status codes

## Examples

Example (lets found all valid users ids):

```bash
crunch 1 6 0123456789 | sed sed s/^/http:\\/\\/site.com\\/user\\//account_id\\// | masscurl -m f,ss
```

Example output:

	http://site.com/user/account_id/0 404
	http://site.com/user/account_id/1 404
	http://site.com/user/account_id/2 200
	http://site.com/user/account_id/3 200
