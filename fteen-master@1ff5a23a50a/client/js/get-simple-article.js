require('es6-promise').polyfill();
require('isomorphic-fetch');

module.exports = {
    getSimplified: function(articleId){
        return fetch('thing/'+articleId)
            .then(function(response) {
                if (response.status >= 400) {
                    throw new Error("Bad response from server");
                }
                return response.json();
            });
    },
    getNormal: function(articleId){
        return fetch('http://next-elastic.ft.com/v3_api_v2/item/'+articleId)
            .then(function(response) {
                if (response.status >= 400) {
                    throw new Error("Bad response from server");
                }
                return response.json();
            });
    }
};



