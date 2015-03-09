# Manticorp_SphinxSearch

This extension integrates [Sphinx Search](http://sphinxsearch.com/) with any Magento installation.

Based on [Gfe/SphinxSearch](https://github.com/fheyer/sphinxsearch) with a few key improvements.

## Information

Tested on **Magento CE 1.9.1.0**

## Installation

1. *optional* Turn off compilation
2. Copy all the files into the root of your Magento Installation
3. Log out of admin
4. Log back in
5. Flush all caches
6. *optional* Turn on compilation

## Setup

### Sphinx

1. [Install Sphinx Search](http://sphinxsearch.com/docs/current.html#installation)
2. Modify ```sphinx.conf.sample``` to suit your needs
3. Launch Sphinx using the ```searchd``` command

### Magento

1. Go to your magento backend
2. Go to System > Configuration > Catalog > Sphinx Search Engine
3. Turn on 'Enable Search' and 'Enable Index'
4. Update your 'path to sphinx' (leave blank by default if it's in your system path)
5. Apply weights to the necessary fields
6. Exclude whatever fields you wish from attributes
7. Edit the Server configuration if need be (if you set up Sphinx with the default settings, you won't need to do this)
8. Run a catalogsearch_fulltext index

## How it works

### Magento Default Search

By default, Magento chucks all searchable attributes into one fulltext column on the catalogsearch_fulltext index, so you might end up with something like the following:

| fulltext_id | product_id | store_id |    data_index    |
|-------------|------------|----------|------------------|
|    940753   |   225439   |     9    | CPU Desc etc     |

And magento just does a fulltext search against the data_index column with no weighting on any attributes and also includes things like weight, price, etc.

### Sphinx Search

Sphinx Search overrides the fulltext index and creates a new index table that looks more akin to the following:

|    product_id    |    store_id    |    name    |    name_attributes    |    category     | sku |    data_index     |
|------------------|----------------|------------|-----------------------|-----------------|-----|-------------------|
| 12345678         | 9              | CPU        | 3GHz CPU              | Some | Category | Foo | CPU Desc Etc      |

Spinx then creates its own index based on this table and stores it away. The most important parts of this table are the three extra columns name, name_attributes, sku and category.

1. Name is just the product name
2. Name_attributes contains the name + some attributes (configurable in admin)
3. Category contains the category names

Using the magento backend, we can assign weights to these columns in terms of ranking search results.

### Sphinx Server

The sphinx server is configured using the file /var/sphinx/sphinx.conf on Linux *(tested on CentOS)* and contains all sorts of goodies.

Sphinx search has several, much cleverer algorithms for performing searches. A short list of (enabled) features include:

* Query expansion
* Language parsing (detects plurals etc)
* Misspelling correction (barels => barrels)
* ‘Soundex’ queries (cot => cat)

As well as many other behind the scenes stuff. It also ranks searches higher when words appear at the beginning of a field.

For example, a search for ‘Dog’ puts 'dog kennel' before 'log dog'.

## Common Problems

### Sphinx

**Sphinx won't work! It keeps saying I don't have permission to do whatever!**

1. SSH into your sever / open a terminal window.
2. Navigate to the mentioned directory
3. chmod 777 -R {directoryname}

**My searches are return irrelevant results!**

Change which attributes are chosen or play with the weights.