replicache protocol
===================

replicache works using a simple protocol similar to that of [memcached](https://github.com/memcached/memcached).

The protocol is still under active development, and is subject to change at this time.

## CLOSE

CLOSE is used to immediately close the connection to replicache.

	telnet > CLOSE
	BYE

## DELETE

DELETE is used to delete an item from replicache with the specified key.

	telnet > DELETE abc
	OK

## FLUSH

FLUSH is used to remove *all* items from replicache at once.

	telnet > FLUSH
	OK

## GET

GET is used to retrieve an item from replicache with the specified key.  When the item is not found,
replicache will simply return "OK" with no item.

	telnet > GET abc
	OK 123
	telnet > GET def
	OK

## SET

SET is used to set an item in replicache using the specified key.

	telnet > SET abc 123
	OK
	telnet > SET json {"key": "value"}
	OK
