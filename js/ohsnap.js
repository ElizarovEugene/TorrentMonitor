/**
 * == OhSnap!.js ==
 * A simple jQuery/Zepto notification library designed to be used in mobile apps
 *
 * author: Justin Domingue
 * date: september 5, 2013
 * version: 0.1.2
 * copyright - nice copyright over here
 */

function ohSnap(text, color, icon) {
  // text : message to show (HTML tag allowed)
  // Available colors : red, green, blue, orange, yellow --- add your own!
  
  // Set some variables
  var time = '5000';
  var $container = $('#ohsnap');
  
  var icon_markup = "";
  
  if(icon) {
    icon_markup = "<span class='" + icon + "'></span> ";  
  }
  
  // Generate the HTML
  var html = $('<div class="alert alert-' + color + '">' + icon_markup + text + '</div>');

  // Append the label to the container
  $container.append(html);

  // Remove the notification on click
  html.on('click', function () { ohSnapX($(this)); });
  
  // After 'time' seconds, the animation fades out
  setTimeout(function () {
    ohSnapX($container.children('.alert').first());
  }, time);
}

function ohSnapX(element) {
  // Called without argument, the function removes all alerts
  // element must be a jQuery object
  
  if (typeof element !== "undefined" ) {
    element.remove();
  } else {
    $('.alert').remove();
  }
}
