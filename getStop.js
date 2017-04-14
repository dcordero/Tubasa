'use strict';

const http = require('http');

exports.handler = (event, context, callback) => {
    let url = "http://badajoz.twa.es/code/getparadas.php?idl=" + encodeURIComponent(event.idl) + '&idp=' + encodeURIComponent(event.idp) + '&ido=' + encodeURIComponent(event.ido)

    console.log(url)
    const req = http.get(url, (res) => {
        let body = '';
        res.setEncoding('utf8');
        res.on('data', (chunk) => body += chunk);
        res.on('end', () => {

            function toTitleCase(str) {
			    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
		    }

            var nameArray = /Parada:\W(.*?)<\/h3>/g.exec(body);
		    var nextTimeArray = /xima\Whora:<\/span>(.*?)<br/g.exec(body);

            var regex = /<li><span class=\"label\">(.*?):<\/span>(.*?)<\/li>/g;
		    var connectionsRaw = body.match(regex);

		    var connections = [];
		    if (connectionsRaw) {
		        connectionsRaw.forEach(function(entry) {
			        connections.push({
					  "name" : toTitleCase(/<li><span class=\"label\">(.*?)\(.*?:</g.exec(entry)[1]),
					  "next_time" : /span>(.*?)<\/li>/g.exec(entry)[1]
					  });
		        });
		    }

            context.succeed({
			    name: nameArray[1],
			    next_time: nextTimeArray[1],
			    connections: connections
			});
        });
    });
    req.on('error', () => {
        const response = {
        statusCode: 400,
            body: JSON.stringify("")
        };

        context.succeed(response);
    });
    req.end();
};

