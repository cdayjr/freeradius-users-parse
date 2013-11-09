freeradius-users-parse
======================

Read a particularly styled FreeRADIUS users file and generate a new file based on that data

The users file we'll be dealing with will look something like this:
```
### Begin of Users
user1	Cleartext-Password := "pass1"
		Fall-Through = Yes
#==========
user2	Cleartext-Password := "pass2"
		Fall-Through = Yes
# Wed Jun 13 08:43:40 EDT 2012
#==========
user3	Cleartext-Password := "pass3"
		Fall-Through = Yes
# 2012-12-18 11:57:44 EST
#==========
### End of Users
```
