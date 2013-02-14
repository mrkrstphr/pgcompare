# pgcompare

pgcompare is a utility for comparing the schemas of two different PostgreSQL databases.

## About

I wrote this utility back when I was stupid. It pretty much sucks and is written horribly. Maybe someday I'll get around to rewriting this cleanly, and packaging it as a phar so that it's easy to use. 

The goal of pgcompare is to compare a secondary database against a primary database, find all the schema differences, and generate SQL statements to update the secondary to match the primary. 

