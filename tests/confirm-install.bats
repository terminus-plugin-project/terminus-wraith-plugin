#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Composer plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus --version
}

@test "confirm wraith version" {
  wraith --version
}

@test "get help on plugin command" {
  run terminus help wraith
  [[ $output == *"Visual regression testing with Wraith."* ]]
  [ "$status" -eq 0 ]
}
