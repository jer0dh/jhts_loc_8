
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
        }

// Simulate a sample result of an Ajax or REST geocode request
        let fwpResults = {
            results : [
                { lat: '30.0075278',
                    long: '-95.7369283',
                    address: '16125 Country Fair Ln',
                    city: 'Cypress',
                    state: 'TX',
                    zip: '77433',
                    country: 'USA'},
                {
                    lat: '29.9316274',
                    long: '-95.6702606',
                    address: '11655 Green Canyon Dr',
                    city: 'Houston',
                    state: 'TX',
                    zip: '77095',
                    country: 'USA'}
            ]
        }

// Start and bind Vue framework
        let vm = new Vue( {
                el: '#loc-8-component',
                data: {
                    // maybe use props if becomes a component
                    //initial: JSON.parse(JSON.stringify(fwp)),
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
                    uiState: 'view',  //'view, 'edit', 'choice'
                    uiEditTab: 'address', //'address','latlong'
                    request: false,
                    results: [],
                    selected: '',
                    hasLocation: function(){return this.loc.long && this.loc.lat},
                },

                methods: {
                    // View State functions
                    //---------------------------------------------
                    // copies current location to editLoc for user manipulation
                    // and changes to Edit state
                    currentEdit : function() {
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
                    currentRemove : function() {
                        this.loc = this.blankLocation();
                    },

                    // Cancel pressed so go back to View State
                    editCancel : function() {
                        this.uiState="view";
                    },

                    // Will send request to WP to geocode editLoc object
                    geocode : function() {
                        this.request = true;
                        let that = this;
                        setTimeout(
                            function() {
                                that.request = false;
                                that.results = fwpResults.results;
                                that.uiState = 'choice';
                                that.selected = null;
                            }, 500);
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
                        let address=editLoc.address !== ''? editLoc.address: result.address,
                            address2=editLoc.address2 !== ''? editLoc.address2: result.address2,
                            city=editLoc.city !== ''? editLoc.city: result.city,
                            zip=editLoc.zip !== ''? editLoc.zip: result.zip,
                            state=editLoc.state !== ''? editLoc.state: result.state,
                            country=editLoc.country !== ''? editLoc.country: result.country;
                        this.loc.address = address;
                        this.loc.address2 = address2;
                        this.loc.city = city;
                        this.loc.zip = zip;
                        this.loc.country = country;

                        this.uiState = "view";
                    },

                    // If user clicks on address, assign selected to index
                    choiceSelect : function(index){
                        this.selected = index;
                    },

                    // Just use the address inputted and not result
                    // Also used in Edit state
                    choiceUseMyAddress: function() {
                        this.copyLocation(this.editLoc, this.loc);
                        this.uiState="view";
                    },

                    // Utility Functions
                    //-----------------------------------------

                    blankLocation : function() {
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
                        to.lat= from.lat;
                        to.long= from.long;
                        to.address= from.address;
                        to.address2= from.address2;
                        to.city= from.city;
                        to.state= from.state;
                        to.zip= from.zip;
                        to.country= from.country;
                    },

                    fullAddress: function(loc={}) {
                        let result = '';
                        result += loc.address? loc.address : '';
                        result += loc.address2? ', ' + loc.address2 : '';
                        result += loc.city? ', ' + loc.city : '';
                        result += loc.state? ', ' + loc.state : '';
                        result += loc.zip? ', ' + loc.zip : '';
                        result += loc.country? ', ' + loc.country : '';
                        // return result after removing any beginning comma
                        return result.replace(/^,/g, '');
                    }
                }
            }
        );

    });
})(jQuery);

//TODO - add media library 
//TODO - add menu and toolbar to content
//TODO - add security and user checks to save
