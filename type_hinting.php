<?php
  #
  # LICENSE: "THE BEER-WARE LICENSE" (Revision 42)
  # <gregor.mitzka@gmail.com> wrote this file. As long as you retain this notice you
  # can do whatever you want with this stuff. If we meet some day, and you think
  # this stuff is worth it, you can buy me a beer in return Gregor Mitzka
  # 
  # @package      PHP type-hinting for primitive data types
  # @author       Gregor Mitzka <gregor.mitzka@gmail.com>
  # @copyright    2014 (C) Gregor Mitzka
  # @version      1.0
  # @license      The Beer-Ware License
  #
  class TypeHinting {
    const PCRE = '/^Argument (\d+) passed to (?:(\w+)::)?(\w+)\(\) must be an instance of (\w+), (\w+) given/';

    private static $initialized = false;

    #
    # disable instantiation
    #
    final private function __construct() {}
    
    #
    # handle function which get called by the error-handler (registered in the ->initialize function)
    #
    # @param (int) $error_level
    # @param (string) $error_message
    # @return (bool)
    #
    final public static function handle( $error_level, $error_message ) {
      # we only need errors of the type E_RECOVERABLE_ERROR
      if ( $error_level !== E_RECOVERABLE_ERROR ) {
        return false;
      }

      # check if the error message has the expected format, than parse it
      if ( !preg_match( self::PCRE, $error_message, $matched ) ) {
        return false;
      }

      # we only need the expected and given type of the error message
      list( , $hint_argument_index, $hint_class, $hint_function, $hint_expected_type, $hint_given_type ) = $matched;

      # check if the given type matches the expected type
      # e.g. if the expected type is "number" then the given type can be any of "integer" or "double"
      if ( self::validate_type( $hint_expected_type, $hint_given_type ) ) {
        return true;
      }
      
      # the types numeric and callable need a check against the passed value,
      # so we need to get the backtrace, which needs some more time than the other comparisons
      # (callable is a pre-defined type-hint since PHP 5.4)
      if ( self::validate_complex_type( (int) $hint_argument_index, $hint_class, $hint_function, $hint_expected_type ) ) {
        return true;
      }

      # handle the error message if the given type does not match the expected type
      # translate the matched type into a standarized version, e.g. "int" to "integer" and "float" to "double"
      return self::handle_error( self::translate_type( $hint_expected_type ), $error_message, $error_level );
    }
    
    #
    # defines what happens if a type-error occured
    #
    # @param (string) $expected_type: expected type from the function definition
    # @param (string) $error_message: error message of the catched error
    # @param (int) $error_level: error level of the catched error
    # @return (bool)
    # @throws InvalidArgumentException
    #
    protected static function handle_error( $expected_type, $error_message, $error_level ) {
      if ( $expected_type === false ) {
        throw new InvalidArgumentException( $error_message, $error_level );
      }
      
      switch ( $expected_type ) {
        case 'number':
          $replacement = 'a number';
          break;
        case 'scalar':
          $replacement = 'a scalar';
          break;
        case 'numeric':
          $replacement = 'a numeric value';
          break;
        case 'callable':
          $replacement = 'callable';
          break;
        default:
          $replacement = 'of the type ' . $expected_type;
          break;
      }
      
      # replace the string "an instance of <type>" with "of the type <type>"
      throw new InvalidArgumentException( preg_replace( '/(an instance of (?:\w+))/', $replacement, $error_message ), $error_level );
    }

    #
    # initialize the extended type hinting for PHP
    # check if this happend before else register the handle function
    #
    final public static function initialize() {
      self::$initialized or (
        self::$initialized = true and set_error_handler( __CLASS__ . '::handle', E_RECOVERABLE_ERROR )
      );
    }
    
    #
    # translate a type to its standarized version
    # e.g. "int" to "integer" and "float" to "double"
    #
    # @param (string) $type: type name
    # @return (string): standarized type name
    #
    final private static function translate_type( $type ) {
      switch ( $type ) {
        case 'boolean':
        case 'bool':
          return 'boolean';
        case 'integer':
        case 'int':
          return 'integer';
        case 'double':
        case 'float':
        case 'real':
          return 'double';
        case 'string':
          return 'string';
        case 'resource';
          return 'resource';
        case 'number':
          return 'number';
        case 'scalar':
          return 'scalar';
        case 'numeric':
          return 'numeric';
        case 'callable':
          return 'callable';
      }
      
      return false;
    }
    
    #
    # check if the given type matches the expected type
    #
    # @param (string) $expected_type: name of the expected type
    # @param (string) $given_type: name of the given type
    # @return (bool): true if it matches, else false
    #
    final private static function validate_type( $expected_type, $given_type ) {
      switch ( $expected_type ) {
        case 'boolean':
        case 'bool':
          return ( $given_type === 'boolean' );
        case 'integer':
        case 'int':
          return ( $given_type === 'integer' );
        case 'double':
        case 'float':
        case 'real':
          return ( $given_type === 'double' );
        case 'string':
          return ( $given_type === 'string' );
        case 'resource':
          return ( $given_type === 'resource' );
        case 'number':
          return ( $given_type === 'integer' || $given_type === 'double' );
        case 'scalar':
          return (
            $given_type === 'boolean' ||
            $given_type === 'integer' ||
            $given_type === 'double'  ||
            $given_type === 'string'
          );
        # pseudo-type for every type
        case 'mixed':
          return true;
      }
      
      return false;
    }
    
    #
    # check if the passed values are either "numeric" or "callable"
    #
    # @param (int) $argument_index: index of the argument with the wrong value
    # @param (string) $class_name: name of the class where the error occured
    # @param (string) $function_name: name of the function where the error occured
    # @param (string) $expected_type: expected type (numeric or callable)
    # @return (bool) true if the expected type passes the value comparison, else false
    #
    final private static function validate_complex_type( $argument_index, $class_name, $function_name, $expected_type ) {
      # break if the expected type is neither callable nor numeric
      # (so we need not to call "debug_backtrace")
      if ( $expected_type !== 'callable' && $expected_type !== 'numeric' ) {
        return false;
      }
      
      $trace = debug_backtrace();
      
      # try to find the call of the function $function_name (in class $class_name if it's a method call)
      foreach ( $trace as $step ) {
        if ( $function_name === $step[ 'function' ] ) {
          if ( !array_key_exists( 'class', $step ) || $class_name === $step[ 'class' ] ) {
            # get the argument value with the wrong type
            $argument = $step[ 'args' ][ $argument_index - 1 ];

            switch ( $expected_type ) {
              case 'numeric':
                return is_numeric( $argument );
              case 'callable':
                return is_callable( $argument );
            }
          }
        }
      }
      
      return false;
    }
  }
