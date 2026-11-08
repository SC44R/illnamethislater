<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>illnamethislater</title>
  <link rel="icon" href="{{ asset('assets/ui/favicon.png') }}">
  {{-- The pixel font (Press Start 2P) is hosted in public/fonts, so the game needs no internet for text. See css/game.css. --}}
  <link rel="preload" href="{{ asset('fonts/press-start-2p-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ asset('css/game.css') }}">
</head>
<body>
  {{-- The room. The camera (#cam) moves and zooms this whole stage. --}}
  <div id="view">
    <div id="cam">
      <img id="room" alt="" src="{{ asset('assets/room/room_base.png') }}">

      {{-- The laptop screen: the login lives here. Icons AND words. All words come from public/js/text.js. --}}
      <div id="screen">
        <form id="login" autocomplete="off">
          <div class="logo"><span class="l1" id="t-title1"></span><span class="l2" id="t-title2"></span></div>
          <label class="row only-register"><img class="ic" alt="" src="{{ asset('assets/ui/icon_user.png') }}"><input name="name" type="text" maxlength="40" aria-label="Name"></label>
          <label class="row"><span class="at">@</span><input name="email" type="email" aria-label="Email"></label>
          <label class="row"><img class="ic" alt="" src="{{ asset('assets/ui/icon_key.png') }}"><input name="password" type="password" aria-label="Password"></label>
          <div class="buttons">
            <button type="button" id="toggle-register"><img class="ic" alt="" src="{{ asset('assets/ui/icon_new.png') }}"><span class="lbl"></span></button>
            <button type="submit" id="go"><img class="ic" alt="" src="{{ asset('assets/ui/icon_play.png') }}"><span class="lbl"></span></button>
          </div>
          <div id="msg" role="alert"></div>
          <div id="boot"><i></i></div>
        </form>
        <div id="desk"></div>
      </div>

      <div id="spots"></div>
    </div>
  </div>

  <div id="hud"></div>

  <script>
    window.PQ = window.PQ || {};
    PQ.assets = @json(asset('assets'));
    PQ.initialState = @json($state);
  </script>
  <script src="{{ asset('js/text.js') }}"></script>
  <script src="{{ asset('js/util.js') }}"></script>
  <script src="{{ asset('js/beatclick.js') }}"></script>
  <script src="{{ asset('js/effects.js') }}"></script>
  <script src="{{ asset('js/planner.js') }}"></script>
  <script src="{{ asset('js/session.js') }}"></script>
  <script src="{{ asset('js/room.js') }}"></script>
  <script>PQ.room.boot();</script>
</body>
</html>
