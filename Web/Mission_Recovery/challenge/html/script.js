$(function() {
  var data = [
  { 
    action: 'type',
    strings: ['Greetings Adventurer!^400'],
    output: '<span class="gray">I really did not expect to see you here though..</span><br>&nbsp;',
    postDelay: 1000
  },
  { 
    action: 'type',
    strings: ["I've been told that you've been looking for me for quite some time now.^400","Well, yeah, I did, I stole all of your files and I've encrypted those on your computer :D^400","Yeah yeah, I know, it's frustrating.^400"],
    output: ' ',
    postDelay: 1000
  },
  { 
    action: 'type',
    strings: ["But luckily for you, I like challenges! do you?^400"],
    output: '<span class="gray">I dont think you have any other options</span><br>&nbsp;',
    postDelay: 1000
  },
  { 
    action: 'type',
    strings: ["Alright, here's the deal^400","I've hidden some text files around here gathering them will result in having a key that will decrypt your files, I've also left out something (file) for you once you gather all key pieces^400"],
    output: ' ',
    postDelay: 4000
  },
  { 
    action: 'type',
    clear: true,
    strings: ["Here are the files to find^400","run^400"],
    output: $('.mimik-run-output').html()
  },
  { 
    action: 'type',
    strings: ["FACT: even ROBOTS can have feelings, you know?"],
    postDelay: 1000
  }
];
  runScripts(data, 0);
});

function runScripts(data, pos) {
    var prompt = $('.prompt'),
        script = data[pos];
    if(script.clear === true) {
      $('.history').html(''); 
    }
    switch(script.action) {
        case 'type':
          // cleanup for next execution
          prompt.removeData();
          $('.typed-cursor').text('');
          prompt.typed({
            strings: script.strings,
            typeSpeed: 30,
            callback: function() {
              var history = $('.history').html();
              history = history ? [history] : [];
              history.push('$ ' + prompt.text());
              if(script.output) {
                history.push(script.output);
                prompt.html('');
                $('.history').html(history.join('<br>'));
              }
              // scroll to bottom of screen
              $('section.terminal').scrollTop($('section.terminal').height());
              // Run next script
              pos++;
              if(pos < data.length) {
                setTimeout(function() {
                  runScripts(data, pos);
                }, script.postDelay || 1000);
              }
            }
          });
          break;
        case 'view':

          break;
    }
}