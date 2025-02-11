# Backend Developer Test

This package uses Docker Compose to host a small LAMP stack with Symfony on top of it.

## Installation

1. [Install docker](https://docs.docker.com/get-docker/)
2. Clone this repository.
```
git clone https://github.com/leon-v/catch.git
```
3. Bring up the contains with Cocker Compose
```
cd catch
docker compose up
```
5. Wait for the images to be generated which can take a while the first time depending on your hardware.
4. Navigate to the web interface. [http://localhost:80](http://localhost:80)

The datbase should automatically init its schema, but in case you need them, the schema SQL files can be found in [apps/csv_db/schema](apps/csv_db/schema)

### Todo:
 - Tests
 - SEO: Probably won't benefit too much from having what I consider SEO optimisations e.g. Structured Data Markup, Various rel tags, og: tags etc.
 - Use Vue instead of JQuery, but I kinda ran out of time to init a nested project.