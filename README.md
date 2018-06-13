# opencart

In order to run the indexer, please use the following commands:

1. Full Indexer - opencart-base-dir> php -q searchtap-indexer/searchtap_indexer.php -i 1
2. Partial Indexer (to index only table 'gs_searchtap' products) -  opencart-base-dir> php -q searchtap-indexer/searchtap_indexer.php -i 2

Description of gs_searchtap table

product_id - Product Id to be indexed or delete
last_updated_at - date and time when the product is added/removed from the database
last_indexed_at - Initially null and the plugin will automatically update this field after indexing
status - can be a string either "index" or "delete"
