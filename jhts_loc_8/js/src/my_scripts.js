
(function( $ ){

/* javascript code goes here.  Will run after page is loaded in the DOM */
    $(document).ready(function() {
// Simulate the data WP will populate on page creation
        let fwp = {
            lat: '32.3336368',
            long: '-95.2930722',
            address: '1329 S Beckham Ave',
            address2: '',
            city: 'Tyler',
            state: 'TX',
            zip: '75701',
            country: 'USA',
            canEdit: true,
            mapTileLayer: 'https://api.mapbox.com/styles/v1/mapbox/streets-v10/tiles/256/{z}/{x}/{y}?access_token={accessToken}',
            mapAttribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>',
            mapAccessId: 'CodePen',
            mapMaxZoom: 18,
            mapAccessToken: 'pk.eyJ1IjoiamVyMGRoIiwiYSI6ImNpeGo3MGRjaTAwNGIyd280ODJ0dzA1bm4ifQ.tFc-Mw0uY6Zf5056W_R5qw'
        }

// Simulate a sample result of an Ajax or REST geocode request
        let fwpResults = {
            results: [{
                lat: '30.0075278',
                long: '-95.7369283',
                address: '16125 Country Fair Ln',
                city: 'Cypress',
                state: 'TX',
                zip: '77433',
                country: 'USA'
            }, {
                lat: '29.9316274',
                long: '-95.6702606',
                address: '11655 Green Canyon Dr',
                city: 'Houston',
                state: 'TX',
                zip: '77095',
                country: 'USA'
            }, {
                lat: '39.612032',
                long: '-82.904623',
                address: '1476 Lancaster Pike',
                city: 'Circleville',
                state: 'OH',
                zip: '43113',
                country: 'USA'
            }]
        }

        // Vue component as a simple bus to pass events between components
        let bus = new Vue();

//Vue component to allow user to easily switch between address values
Vue.component('double-input', {
    template: `<div>
<span> {{ otherValue }} </span><label v-show="label"> {{ label }}</label>
            <input
              ref="input"
              v-bind:value="value"
              v-on:input="valueChange($event.target.value)"
            v-bind:name="name"  />
<button v-on:click.stop="switchIt()" v-show="canSwitch">Switch</button></div>`,
    props: {
        "value2" : {
            type: String,
            default: ''
        },
        "value" : {
            type: String,
            default: ''
        },
        "name" : {
            type: String
        },
        "label" : {
            type: String
        }
    },
    data: function() {
        return {"otherValue" : '' }
        //no need to emit value2 which
        //means it is read only.
        //so use value2 as initial value of
        //otherValue
    },
    computed: {
        canSwitch : function() {
            return this.otherValue !== '';
        }
    },
    mounted: function() {
        this.otherValue = this.value2;
    },
    methods: {
        valueChange : function(newValue) {
            this.value = newValue;
            this.$emit('input', newValue);
        },
        switchIt : function() {
            let tmp = this.value;
            this.value = this.otherValue;
            this.otherValue = tmp;
            this.$emit('input', this.value);
        }
    }
})
// Vue component to hold the Leaflet Map
// Pass in the Location object containing the lat and long in props
// Added ability to add an array for multiple markers.  It will emit the
// index of the location array that was clicked. Using a simple bus
// Currently does not update the map if lat/long change in the array
Vue.component('map-component', {
    template: '<div class="aMap"></div>',
    props: ['loc'],
    data: function() {
        return {
            map: {},
            markers: []
        }
    },
    // Initialize map
    mounted: function() {
        this.map = L.map(this.$el);
        L.tileLayer(fwp.mapTileLayer, {
            attribution: fwp.mapAttribution,
            maxZoom: fwp.mapMaxZoom,
            id: fwp.mapAccessId,
            accessToken: fwp.mapAccessToken
        }).addTo(this.map);

        if (Array.isArray(this.loc)) {
            console.log(this.loc);
            for(let i = 0; i < this.loc.length; i++){
                let marker = L.marker([this.loc[i].lat, this.loc[i].long])
                    .addTo(this.map)
                    .on('click', this.markerClick);
                marker.loc_8_id = i;
                this.markers.push( marker );
            }
            let group = new L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.5));
        } else {
            let marker = L.marker([this.loc.lat, this.loc.long]).addTo(this.map);
            this.markers[0] = marker;
            this.map.setView([this.loc.lat, this.loc.long], 13);
        }
    },
    methods: {
        markerClick: function(e) {
            console.log("emitting" + e.target.loc_8_id);
            bus.$emit('marker-click', e.target.loc_8_id);
        }
    },
    // if Location changes from parent,
    // update map
    watch: {
        'loc': {
            deep: true,
            handler: function() {
                console.log("a map given new coords");
                if(Array.isArray(this.loc)) {
                    //To implement if needed.
                } else {
                    this.map.setView([this.loc.lat, this.loc.long], 13);
                    this.markers[0] = L.marker([this.loc.lat, this.loc.long]).addTo(this.map);
                }
            }
        }
    },

        });

// Start and bind Vue framework
        let vm = new Vue({
            el: '#loc-8-component',
            data: {
                loc: {
                    lat: fwp.lat,
                    long: fwp.long,
                    address: fwp.address,
                    address2: fwp.address2,
                    city: fwp.city,
                    state: fwp.state,
                    zip: fwp.zip,
                    country: fwp.country,
                },
                editLoc: {},
                canEdit: fwp.canEdit,
                uiState: 'view', //'view, 'edit', 'choice'
                request: false,
                results: [],
                selected: '',
                labels: []

            },

            watch: {

    },
    mounted: function() {
        console.log('setting up $on')
        let that = this;
        bus.$on('marker-click', function(s) {
            console.log('in bus.$on:masterClick with ' + s)
            that.selected = s;
        });
        let alpha = "ABCDEFGHIJKLMNOP";
        this.labels = alpha.split('');
    },
    computed: {
        hasLocation: function() {
            return this.loc.long && this.loc.lat
        },
        editAddressChange : function() {
            return this.fullAddress(this.loc).toLowercase() !== this.fullAddress(this.editLoc).toLowercase();
        },
        editLocationChange: function() {
            return (this.loc.lat + this.loc.long) !== (this.editLoc.lat + this.editLoc.long);
        }


    },
    methods: {
        // View State functions
        //---------------------------------------------
        // copies current location to editLoc for user manipulation
        // and changes to Edit state
        currentEdit: function() {
            this.editLoc = JSON.parse(JSON.stringify(this.loc));
            this.uiState = 'edit';
        },

        // Edit State functions
        //---------------------------------------------

        // Shows either address edit screen or lat/long edit screen
        setUiEditTab: function(tab) {
            this.uiEditTab = tab;
        },

                // Remove pressed so blank out location
                currentRemove: function() {
                    this.loc = this.blankLocation();
                },

                // Cancel pressed so go back to View State
                editCancel: function() {
                    this.uiState = "view";
                },

                // Will send request to WP to geocode editLoc object
                geocode: function() {
                    this.request = true;
                    let that = this;
                    //Get the inputs for this component
                    let data = {};
                    $.each($('input[name^=geo_loc_8_]'),function(i,v){data[$(v).attr('name')] = $(v).val()});
                    data['action'] = 'loc_8_geocode';
                    //TODO obtain url and action via localscript
                    $.ajax('http://staging3.adv.jhtechservices.com/wp-admin/admin-ajax.php',{
                        method: 'POST',
                        data: data ,

                    } )
                        .done( (results) => {
                            console.log(results);
                        })
                        .fail( (error) => {
                            console.log('failed: ' + error);
                        })
                        .always( () => {
                            that.request = false;
                        });

/*                    setTimeout(
                        function() {
                            that.request = false;
                            that.results = fwpResults.results;
                            that.uiState = 'choice';
                            that.selected = null;
                        }, 500);*/
                },

                // The Choice State
                //-----------------------------------------------

                // Use the selected returned location so copy to loc
                // and change to View state
                choiceUseSelected: function() {
                    this.copyLocation(this.results[this.selected], this.loc);
                    this.uiState = 'view';
                },

                // Use the selected results Lat/Long but use any inputted
                // field value from editLoc
                choiceUseSelectedLocation: function() {
                    let result = this.results[this.selected];
                    let editLoc = this.editLoc;
                    this.loc.lat = result.lat;
                    this.loc.long = result.long;
                    // if editLoc prop has a value use it, otherwise use result
                    let address = editLoc.address !== '' ? editLoc.address : result.address,
                        address2 = editLoc.address2 !== '' ? editLoc.address2 : result.address2,
                        city = editLoc.city !== '' ? editLoc.city : result.city,
                        zip = editLoc.zip !== '' ? editLoc.zip : result.zip,
                        state = editLoc.state !== '' ? editLoc.state : result.state,
                        country = editLoc.country !== '' ? editLoc.country : result.country;
                    this.loc.address = address;
                    this.loc.address2 = address2;
                    this.loc.city = city;
                    this.loc.zip = zip;
                    this.loc.country = country;

                    this.uiState = "view";
                },

                // If user clicks on address, assign selected to index
                choiceSelect: function(index) {
                    this.selected = index;
                },

                // Just use the address inputted and not result
                // Also used in Edit state
                choiceUseMyAddress: function() {
                    this.copyLocation(this.editLoc, this.loc);
                    this.uiState = "view";
                },

                // Utility Functions
                //-----------------------------------------

                blankLocation: function() {
                    return {
                        lat: '',
                        long: '',
                        address: '',
                        address2: '',
                        city: '',
                        state: '',
                        zip: '',
                        country: '',
                    }
                },
                copyLocation: function(from, to) {
                    to.lat = from.lat;
                    to.long = from.long;
                    to.address = from.address;
                    to.address2 = from.address2;
                    to.city = from.city;
                    to.state = from.state;
                    to.zip = from.zip;
                    to.country = from.country;
                },
                fullAddress: function(loc = {}) {
                    let result = '';
                    result += loc.address ? loc.address : '';
                    result += loc.address2 ? ', ' + loc.address2 : '';
                    result += loc.city ? ', ' + loc.city : '';
                    result += loc.state ? ', ' + loc.state : '';
                    result += loc.zip ? ', ' + loc.zip : '';
                    result += loc.country ? ', ' + loc.country : '';
                    // return result after removing any beginning comma
                    return result.replace(/^,/g, '');
                }

            },

        });
        });
})(jQuery);

//TODO - add media library 
//TODO - add menu and toolbar to content
//TODO - add security and user checks to save
