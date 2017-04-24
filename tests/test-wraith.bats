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
  run terminus wraith --sites basic-testing.md,basic-testing.dev --paths home=/,user=/user -n
  [[ "$output" == *"[notice] Gallery generated"* ]]
  [ "$status" -eq 0 ]
}
