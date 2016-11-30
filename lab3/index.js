/**
Sample event:
{
  "bucket": "janci",
  "file": "google-10000-english-no-swears.csv",
  "table": "10k-list"
}
**/

var BreakException = {};
var util = require('util');
var AWS = require('aws-sdk');

var S = new AWS.S3({
    maxRetries: 0,
    region: 'eu-west-1',
});

var docClient = new AWS.DynamoDB.DocumentClient();
var table = "10k-list";


exports.handler = (event, context, callback) => {
    // Read options from the event.
    console.log("Reading options from event:\n", util.inspect(event, {depth: 5}));

    var bucket = event.bucket;
    var file = event.file;
    var table = event.table;

    // don't run on anything that isn't a CSV
    if (file.match(/\.csv$/) === null) {
        var msg = "File " + file + " is not a csv file, bailing out";
        console.log(msg);
        return callback(null, {message: msg});
    }

    var imported, errors;
    var items = 0;

    S.getObject({
        Bucket: bucket,
        Key: file,
    }, function (err, data) {
        if (err !== null) { return callback(err, null); }

        var lines = data.Body.toString('utf-8').split('\n');
        items = lines.length;

        imported = 0;
        errors = 0;
        processed = 0;
        try {
            lines.slice(0).forEach(function (line) {
                processed++;

                if (line.match(/^\s*$/) !== null) {
                    return;
                }

                docClient.put({
                    TableName: table,
                    Item: {
                        "word": line
                    }
                }, function(err, data) {
                    if (err) console.log(err);
                    else console.log(data);
                });

                if (processed > 10) {
                    throw BreakException;
                }
            });
        } catch (e) {}

        return callback(null, {
            'bucket' : bucket,
            'file': file, 
            'table': table,
            'items': items,
            'processed': processed
            });

    });
}