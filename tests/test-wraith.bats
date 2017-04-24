#!/usr/bin/env bats

#
# test-wraith.bats
#
# Test plugin 'wraith' command
#

@test "output of plugin 'wraith' command" {
  run rm -rf configs/ javascript/
  run wraith setup
  [[ "$output" == *"create  javascript/wait--phantom.js"* ]]
  [ "$status" -eq 0 ]
  run terminus wraith --sites $TERMINUS_SOURCE_SITE_ENV,$TERMINUS_TARGET_SITE_ENV --paths home=/,user=/user -n
  [[ "$output" == *"Gallery generated"* ]]
  [ "$status" -eq 0 ]
}
