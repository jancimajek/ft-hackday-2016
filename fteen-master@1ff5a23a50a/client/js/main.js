// Require module
//596ec790-afe8-11e6-9c37-5787335499a0/
//document.getElementById("article-col").innerHTML
const articleId = "596ec790-afe8-11e6-9c37-5787335499a0";
const oHeader = require('o-header');
const getArticle = require('./get-simple-article.js');

getArticle.getNormal(articleId)
	.then(function(json) {
        if (document.readyState === 'interactive' || document.readyState === 'complete') {
            document.getElementById("article-title").innerHTML = json._source.title;
            document.getElementById("article-col").innerHTML = json._source.bodyHTML;
            document.dispatchEvent(new CustomEvent('o.DOMContentLoaded'));
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Dispatch a custom event that will tell all required modules to initialise

            document.dispatchEvent(new CustomEvent('o.DOMContentLoaded'));
        });
});

oHeader.init();


// Wait until the page has loaded


