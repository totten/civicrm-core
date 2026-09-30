# search_kit_fts
(*FIXME: In one or two paragraphs, describe what the extension does and why one would download it. *)

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).

## Getting Started

(* FIXME: Where would a new user navigate to get started? What changes would they see? *)

## Development

To run tests for FTS integration drivers (Solr, ElasticSearch, TypeSense), you will need instances of each service.
The bundled Docker configuration will help test the drivers:

```bash
docker compose -f tests/docker-compose.yml up
cv vset fts_solr_url=http://localhost:8983/ fts_typesense_url=http://localhost:8108/ fts_elastic_url=http://localhost:9200/
```

## Known Issues

(* FIXME *)
