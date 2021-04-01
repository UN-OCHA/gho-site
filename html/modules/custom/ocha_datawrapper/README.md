# OCHA Datawrapper

Make charts available and allow users to create new charts.

## Chart templates

Provide empty charts (without data) for different scenarios,
so users can easily select one as starting block to build a chart
with custom data.

## Data sources

### Semi-static data

Can be passed to a chart using the API and formatted as csv data string,
see https://developer.datawrapper.de/docs/creating-a-chart-new#upload-data

### Dynamic data

Can be either hot linked, or cached on the datawrapper servers. Best to use hot linking
to our own site so we can control the update frequency (using cache tags).

User would ideally select which data to display, filter by either country, cluster, ...

## Embedding the chart

Since all building blocks are paragraph, we can add a new type so the user
can select which chart needs to be displayed.
