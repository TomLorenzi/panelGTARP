const HomePage = {};

//https://github.com/Johann-S/bs-stepper/tree/v1.7.0#methods
HomePage.init = function() {
    console.log($('#stepper'));
    const stepper = new Stepper($('#stepper')[0]);
};


$(document).ready(function () {
    HomePage.init();
})