%name PC_Type_
%declare_class {class PC_Engine_TypeParser}

%syntax_error {
    foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
        $expect[] = self::$yyTokenName[$token];
    }
		throw new PC_Engine_Exception(
			$this->state->get_file(),$this->state->get_line(),$this->tokenName($yymajor),$TOKEN,$expect
		);
}

%include_class {
		// current state, i.e. the function, class and variables in scope
		private $state;
		
		public $transTable = array();

    function __construct($state)
    {
				$this->state = $state;
				if(!count($this->transTable))
				{
					$start = 240; // start nice and low to be sure
					while(token_name($start) == 'UNKNOWN')
						$start++;
					$hash = array_flip(self::$yyTokenName);
					$map = array(
						ord(',') => self::COMMA,
						ord('=') => self::EQUALS,
						ord('?') => self::QUESTION,
						ord(':') => self::COLON,
						ord('|') => self::BAR,
						ord('^') => self::CARAT,
						ord('&') => self::AMPERSAND,
						ord('<') => self::LESSTHAN,
						ord('>') => self::GREATERTHAN,
						ord('+') => self::PLUS,
						ord('-') => self::MINUS,
						ord('.') => self::DOT,
						ord('*') => self::TIMES,
						ord('/') => self::DIVIDE,
						ord('%') => self::PERCENT,
						ord('!') => self::EXCLAM,
						ord('~') => self::TILDE,
						ord('@') => self::AT,
						ord('[') => self::LBRACKET,
						ord('(') => self::LPAREN,
						ord(')') => self::RPAREN,
						ord(';') => self::SEMI,
						ord('{') => self::LCURLY,
						ord('}') => self::RCURLY,
						ord('`') => self::BACKQUOTE,
						ord('$') => self::DOLLAR,
						ord(']') => self::RBRACKET,
						ord('"') => self::DOUBLEQUOTE,
						ord("'") => self::SINGLEQUOTE,
					);
					for($i = $start; $i < self::YYERRORSYMBOL + $start; $i++)
					{
						$lt = token_name($i);
            $lt = ($lt == 'T_ML_COMMENT') ? 'T_COMMENT' : $lt;
						$lt = ($lt == 'T_DOUBLE_COLON') ?  'T_PAAMAYIM_NEKUDOTAYIM' : $lt;
						if(!isset($hash[$lt]))
							continue;
		
						$map[$i] = $hash[$lt];
					}
					$this->transTable = $map;
				}
    }
}

%left T_INCLUDE T_INCLUDE_ONCE T_EVAL T_REQUIRE T_REQUIRE_ONCE.
%left COMMA.
%left T_LOGICAL_OR.
%left T_LOGICAL_XOR.
%left T_LOGICAL_AND.
%right T_PRINT.
%left EQUALS T_PLUS_EQUAL T_MINUS_EQUAL T_MUL_EQUAL T_DIV_EQUAL T_CONCAT_EQUAL T_MOD_EQUAL T_AND_EQUAL T_OR_EQUAL T_XOR_EQUAL T_SL_EQUAL T_SR_EQUAL.
%left QUESTION COLON.
%left T_BOOLEAN_OR.
%left T_BOOLEAN_AND.
%left BAR.
%left CARAT.
%left AMPERSAND.
%nonassoc T_IS_EQUAL T_IS_NOT_EQUAL T_IS_IDENTICAL T_IS_NOT_IDENTICAL.
%nonassoc LESSTHAN T_IS_SMALLER_OR_EQUAL GREATERTHAN T_IS_GREATER_OR_EQUAL.
%left T_SL T_SR.
%left PLUS MINUS DOT.
%left TIMES DIVIDE PERCENT.
%right EXCLAM.
%nonassoc T_INSTANCEOF.
%right TILDE T_INC T_DEC T_INT_CAST T_DOUBLE_CAST T_STRING_CAST T_ARRAY_CAST T_OBJECT_CAST T_BOOL_CAST T_UNSET_CAST AT.
%right LBRACKET.
%nonassoc T_NEW T_CLONE.
%left T_ELSEIF.
%left T_ELSE.
%left T_ENDIF.
%right T_STATIC T_ABSTRACT T_FINAL T_PRIVATE T_PROTECTED T_PUBLIC.

start ::= top_statement_list.

top_statement_list ::= top_statement_list top_statement.
top_statement_list ::= .

top_statement ::= statement.
top_statement ::= function_declaration_statement.
top_statement ::= class_declaration_statement.
top_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI.

statement ::= unticked_statement.

unticked_statement ::= LCURLY inner_statement_list RCURLY.
unticked_statement ::= T_IF LPAREN expr RPAREN statement elseif_list else_single.
unticked_statement ::= T_IF LPAREN expr RPAREN COLON inner_statement_list new_elseif_list new_else_single T_ENDIF COLON.
unticked_statement ::= T_WHILE LPAREN expr RPAREN while_statement.
unticked_statement ::= T_DO statement T_WHILE LPAREN expr RPAREN SEMI.
unticked_statement ::= T_FOR 
			LPAREN
				for_expr
			SEMI 
				for_expr
			SEMI
				for_expr
			RPAREN
			for_statement.
unticked_statement ::= T_SWITCH LPAREN expr RPAREN switch_case_list.
unticked_statement ::= T_BREAK SEMI.
unticked_statement ::= T_BREAK expr SEMI.
unticked_statement ::= T_CONTINUE SEMI.
unticked_statement ::= T_CONTINUE expr SEMI.
unticked_statement ::= T_RETURN SEMI.
unticked_statement ::= T_RETURN expr_without_variable SEMI.
unticked_statement ::= T_RETURN variable SEMI.
unticked_statement ::= T_GLOBAL global_var_list SEMI.
unticked_statement ::= T_STATIC static_var_list SEMI.
unticked_statement ::= T_ECHO echo_expr_list SEMI.
unticked_statement ::= T_INLINE_HTML.
unticked_statement ::= expr SEMI.
unticked_statement ::= T_USE use_filename SEMI.
unticked_statement ::= T_UNSET LPAREN unset_variables RPAREN SEMI.
unticked_statement ::= T_FOREACH LPAREN variable T_AS 
		foreach_variable foreach_optional_arg RPAREN
		foreach_statement.
unticked_statement ::= T_FOREACH LPAREN expr_without_variable T_AS 
		w_variable foreach_optional_arg RPAREN
		foreach_statement.
unticked_statement ::= T_DECLARE LPAREN declare_list RPAREN declare_statement.
unticked_statement ::= SEMI.
unticked_statement ::= T_TRY LCURLY inner_statement_list RCURLY
		T_CATCH LPAREN
		fully_qualified_class_name
		T_VARIABLE RPAREN
		LCURLY inner_statement_list RCURLY
		additional_catches.
unticked_statement ::= T_THROW expr SEMI.

additional_catches ::= non_empty_additional_catches.
additional_catches ::= .

non_empty_additional_catches ::= additional_catch.
non_empty_additional_catches ::= non_empty_additional_catches additional_catch.

additional_catch ::= T_CATCH LPAREN fully_qualified_class_name T_VARIABLE RPAREN LCURLY inner_statement_list RCURLY.

inner_statement_list ::= inner_statement_list inner_statement.
inner_statement_list ::= .

inner_statement ::= statement.
inner_statement ::= function_declaration_statement.
inner_statement ::= class_declaration_statement.
inner_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI.

function_declaration_statement ::= unticked_function_declaration_statement.

class_declaration_statement ::= unticked_class_declaration_statement.

unticked_function_declaration_statement ::=
		T_FUNCTION is_reference T_STRING(name) LPAREN parameter_list(params) RPAREN
		LCURLY inner_statement_list RCURLY. {
		$this->state->declare_function(name,params);
}

unticked_class_declaration_statement ::=
		class_entry_type(modifier) T_STRING(name) extends_from(extends)
			implements_list(implements)
			LCURLY
				class_statement_list(stmts)
			RCURLY. {
		$this->state->declare_class(
			name,modifier->metadata,extends,implements->metadata,stmts
		);
}
unticked_class_declaration_statement ::=
		interface_entry T_STRING(name)
			interface_extends_list(extends)
			LCURLY
				class_statement_list(stmts)
			RCURLY. {
		$this->state->declare_interface(name,extends->metadata,stmts);
}

class_entry_type(A) ::= T_CLASS. { A = new PC_Type_yyToken(''); }
class_entry_type(A) ::= T_ABSTRACT T_CLASS. { A = new PC_Type_yyToken('',array('abstract' => true)); }
class_entry_type(A) ::= T_FINAL T_CLASS. { A = new PC_Type_yyToken('',array('final' => true)); }

extends_from(A) ::= T_EXTENDS fully_qualified_class_name(name). { A = name->string; }
extends_from(A) ::= . { A = null; }

interface_entry ::= T_INTERFACE.

interface_extends_list(A) ::= T_EXTENDS interface_list(list). { A = new PC_Type_yyToken(list); }
interface_extends_list(A) ::= . { A = new PC_Type_yyToken('',array()); }

implements_list(A) ::= . { A = new PC_Type_yyToken('',array()); }
implements_list(A) ::= T_IMPLEMENTS interface_list(list). { A = new PC_Type_yyToken(list); }

interface_list(A) ::= fully_qualified_class_name(name). {
	A = new PC_Type_yyToken('');
	A[] = array(name->string);
}
interface_list(A) ::= interface_list(list) COMMA fully_qualified_class_name(name). {
	A = new PC_Type_yyToken(list);
	A[] = array(name->string);
}

expr ::= r_variable.
expr ::= expr_without_variable.

expr_without_variable ::= T_LIST LPAREN assignment_list RPAREN EQUALS expr.
expr_without_variable ::= variable EQUALS expr.
expr_without_variable ::= variable EQUALS AMPERSAND variable.
expr_without_variable ::= variable EQUALS AMPERSAND T_NEW class_name_reference ctor_arguments.
expr_without_variable ::= T_NEW class_name_reference ctor_arguments.
expr_without_variable ::= T_CLONE expr.
expr_without_variable ::= variable T_PLUS_EQUAL expr.
expr_without_variable ::= variable T_MINUS_EQUAL expr.
expr_without_variable ::= variable T_MUL_EQUAL expr.
expr_without_variable ::= variable T_DIV_EQUAL expr.
expr_without_variable ::= variable T_CONCAT_EQUAL expr.
expr_without_variable ::= variable T_MOD_EQUAL expr.
expr_without_variable ::= variable T_AND_EQUAL expr.
expr_without_variable ::= variable T_OR_EQUAL expr.
expr_without_variable ::= variable T_XOR_EQUAL expr.
expr_without_variable ::= variable T_SL_EQUAL expr.
expr_without_variable ::= variable T_SR_EQUAL expr.
expr_without_variable ::= rw_variable T_INC.
expr_without_variable ::= T_INC rw_variable.
expr_without_variable ::= rw_variable T_DEC.
expr_without_variable ::= T_DEC rw_variable.
expr_without_variable ::= expr T_BOOLEAN_OR expr.
expr_without_variable ::= expr T_BOOLEAN_AND expr.
expr_without_variable ::= expr T_LOGICAL_OR expr.
expr_without_variable ::= expr T_LOGICAL_AND expr.
expr_without_variable ::= expr T_LOGICAL_XOR expr.
expr_without_variable ::= expr BAR expr.
expr_without_variable ::= expr AMPERSAND expr.
expr_without_variable ::= expr CARAT expr.
expr_without_variable ::= expr DOT expr.
expr_without_variable ::= expr PLUS expr.
expr_without_variable ::= expr MINUS expr.
expr_without_variable ::= expr TIMES expr.
expr_without_variable ::= expr DIVIDE expr.
expr_without_variable ::= expr PERCENT expr.
expr_without_variable ::= expr T_SL expr.
expr_without_variable ::= expr T_SR expr.
expr_without_variable ::= PLUS expr.
expr_without_variable ::= MINUS expr.
expr_without_variable ::= EXCLAM expr.
expr_without_variable ::= TILDE expr.
expr_without_variable ::= expr T_IS_IDENTICAL expr.
expr_without_variable ::= expr T_IS_NOT_IDENTICAL expr.
expr_without_variable ::= expr T_IS_EQUAL expr.
expr_without_variable ::= expr T_IS_NOT_EQUAL expr.
expr_without_variable ::= expr LESSTHAN expr.
expr_without_variable ::= expr T_IS_SMALLER_OR_EQUAL expr.
expr_without_variable ::= expr GREATERTHAN expr.
expr_without_variable ::= expr T_IS_GREATER_OR_EQUAL expr.
expr_without_variable ::= expr T_INSTANCEOF class_name_reference.
expr_without_variable ::= LPAREN expr RPAREN.
expr_without_variable ::= expr QUESTION
		expr COLON
		expr.
expr_without_variable ::= internal_functions_in_yacc.
expr_without_variable ::= T_INT_CAST expr.
expr_without_variable ::= T_DOUBLE_CAST expr.
expr_without_variable ::= T_STRING_CAST expr.
expr_without_variable ::= T_ARRAY_CAST expr.
expr_without_variable ::= T_OBJECT_CAST expr.
expr_without_variable ::= T_BOOL_CAST expr.
expr_without_variable ::= T_UNSET_CAST expr.
expr_without_variable ::= T_EXIT exit_expr.
expr_without_variable ::= AT expr.
expr_without_variable(A) ::= scalar(sc). { A = sc; }
expr_without_variable ::= T_ARRAY LPAREN array_pair_list RPAREN.
expr_without_variable ::= BACKQUOTE encaps_list BACKQUOTE.
expr_without_variable ::= T_PRINT expr.

exit_expr ::= LPAREN RPAREN.
exit_expr ::= LPAREN expr RPAREN.
exit_expr ::= .

common_scalar(A) ::= T_LNUMBER(sval). { A = PC_Obj_MultiType::create_int(sval); }
common_scalar(A) ::= T_DNUMBER(sval). { A = PC_Obj_MultiType::create_float(sval); }
common_scalar(A) ::= T_CONSTANT_ENCAPSED_STRING(sval). {
	A = PC_Obj_MultiType::create_string(substr(sval,1,-1));
}
common_scalar(A) ::= T_LINE. { A = PC_Obj_MultiType::create_int($this->state->get_line()); }
common_scalar(A) ::= T_FILE. { A = PC_Obj_MultiType::create_string($this->state->get_file()); }
common_scalar(A) ::= T_CLASS_C|T_METHOD_C|T_FUNC_C. {
	// TODO value
	A = PC_Obj_MultiType::create_string();
}

/* compile-time evaluated scalars */
static_scalar(A) ::= common_scalar(sval). { A = sval; }
static_scalar(A) ::= T_STRING(sval). {
	if(strcasecmp(sval,"true") == 0)
		A = PC_Obj_MultiType::create_bool(true);
	else if(strcasecmp(sval,"false") == 0)
		A = PC_Obj_MultiType::create_bool(false);
	else 
		A = $this->state->get_constant_type(sval);
}
static_scalar(A) ::= PLUS static_scalar(sval). {
	A = $this->state->handle_unary_op('+',new PC_Obj_Variable('',sval))->get_type();
}
static_scalar(A) ::= MINUS static_scalar(sval). {
	A = $this->state->handle_unary_op('-',new PC_Obj_Variable('',sval))->get_type();
}
static_scalar(A) ::= T_ARRAY LPAREN static_array_pair_list(list) RPAREN. { A = list; }
static_scalar(A) ::= static_class_constant. { /* TODO */ A = null; }

static_array_pair_list(A) ::= non_empty_static_array_pair_list(list). { A = list; }
static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA. { A = list; }
static_array_pair_list(A) ::= . { A = PC_Obj_MultiType::create_array(); }

non_empty_static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA
																				static_scalar(skey) T_DOUBLE_ARROW static_scalar(sval). {
	A = list;
	A->get_first()->set_array_type(skey,sval);
}
non_empty_static_array_pair_list(A) ::= non_empty_static_array_pair_list(list) COMMA
																				static_scalar(sval). {
	A = list;
	A->get_first()->set_array_type(A->get_first()->get_next_array_key(),sval);
}
non_empty_static_array_pair_list(A) ::= static_scalar(skey) T_DOUBLE_ARROW static_scalar(sval). {
	A = PC_Obj_MultiType::create_array();
	A->get_first()->set_array_type(skey,sval);
}
non_empty_static_array_pair_list(A) ::= static_scalar(sval). {
	A = PC_Obj_MultiType::create_array();
	A->get_first()->set_array_type(0,sval);
}

static_class_constant ::= T_STRING T_PAAMAYIM_NEKUDOTAYIM T_STRING.

foreach_optional_arg ::= T_DOUBLE_ARROW foreach_variable.
foreach_optional_arg ::= .

foreach_variable ::= w_variable.
foreach_variable ::= AMPERSAND w_variable.

for_statement ::= statement.
for_statement ::= COLON inner_statement_list T_ENDFOR SEMI.

foreach_statement ::= statement.
foreach_statement ::= COLON inner_statement_list T_ENDFOREACH SEMI.


declare_statement ::= statement.
declare_statement ::= COLON inner_statement_list T_ENDDECLARE SEMI.

declare_list ::= T_STRING EQUALS static_scalar.
declare_list ::= declare_list COMMA T_STRING EQUALS static_scalar.

switch_case_list ::= LCURLY case_list RCURLY.
switch_case_list ::= LCURLY SEMI case_list RCURLY.
switch_case_list ::= COLON case_list T_ENDSWITCH SEMI.
switch_case_list ::= COLON SEMI case_list T_ENDSWITCH SEMI.

case_list ::= case_list T_CASE expr case_separator inner_statement_list.
case_list ::= case_list T_DEFAULT case_separator inner_statement_list.
case_list ::= .

case_separator ::= COLON|SEMI.

while_statement ::= statement.
while_statement ::= COLON inner_statement_list T_ENDWHILE SEMI.

elseif_list ::= elseif_list T_ELSEIF LPAREN expr RPAREN statement.
elseif_list ::= .

new_elseif_list ::= new_elseif_list T_ELSEIF LPAREN expr RPAREN COLON inner_statement_list .
new_elseif_list ::= .

else_single ::= T_ELSE statement.
else_single ::= .

new_else_single ::= T_ELSE COLON inner_statement_list.
new_else_single ::= .

parameter_list(A) ::= non_empty_parameter_list(list). { A = list; }
parameter_list(A) ::= . { A = array(); }

non_empty_parameter_list(A) ::= optional_class_type(vtype) T_VARIABLE(vname). {
	A = array();
	$param = new PC_Obj_Parameter(vname);
	$param->set_mtype(vtype);
	A[] = $param;
}
non_empty_parameter_list(A) ::= optional_class_type(vtype) AMPERSAND T_VARIABLE(vname). {
	A = array();
	$param = new PC_Obj_Parameter(vname);
	$param->set_mtype(vtype);
	A[] = $param;
}
non_empty_parameter_list(A) ::= optional_class_type(vtype) AMPERSAND T_VARIABLE(vname)
																EQUALS static_scalar(vval). {
	A = array();
	$param = new PC_Obj_Parameter(vname);
	if(vval)
		vval->clear_values(); // value is not interesting here
	if(vval && vtype->is_unknown())
		$param->set_mtype(vval);
	else
		$param->set_mtype(vtype);
	$param->set_optional(true);
	A[] = $param;
}
non_empty_parameter_list(A) ::= optional_class_type(vtype) T_VARIABLE(vname)
																EQUALS static_scalar(vval). {
	A = array();
	$param = new PC_Obj_Parameter(vname);
	if(vval)
		vval->clear_values(); // value is not interesting here
	if(vval && vtype->is_unknown())
		$param->set_mtype(vval);
	else
		$param->set_mtype(vtype);
	$param->set_optional(true);
	A[] = $param;
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA optional_class_type(vtype)
																T_VARIABLE(vname). {
	A = list;
	$param = new PC_Obj_Parameter(vname);
	$param->set_mtype(vtype);
	A[] = $param;
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA optional_class_type(vtype)
																AMPERSAND T_VARIABLE(vname). {
	A = list;
	$param = new PC_Obj_Parameter(vname);
	$param->set_mtype(vtype);
	A[] = $param;
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA optional_class_type(vtype)
																AMPERSAND T_VARIABLE(vname) EQUALS static_scalar(vval). {
	A = list;
	$param = new PC_Obj_Parameter(vname);
	if(vval)
		vval->clear_values(); // value is not interesting here
	if(vval && vtype->is_unknown())
		$param->set_mtype(vval);
	else
		$param->set_mtype(vtype);
	$param->set_optional(true);
	A[] = $param;
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA optional_class_type(vtype)
																T_VARIABLE(vname) EQUALS static_scalar(vval). {
	A = list;
	$param = new PC_Obj_Parameter(vname);
	if(vval)
		vval->clear_values(); // value is not interesting here
	if(vval && vtype->is_unknown())
		$param->set_mtype(vval);
	else
		$param->set_mtype(vtype);
	$param->set_optional(true);
	A[] = $param;
}


optional_class_type(A) ::= T_STRING(vtype). { A = PC_Obj_MultiType::create_object(vtype); }
optional_class_type(A) ::= T_ARRAY. { A = PC_Obj_MultiType::create_array(); }
optional_class_type(A) ::= . { A = new PC_Obj_MultiType(); }

function_call_parameter_list(A) ::= non_empty_function_call_parameter_list(list). { A = list; }
function_call_parameter_list(A) ::= . { A = array(); }

non_empty_function_call_parameter_list(A) ::= expr_without_variable(e). { A = array(e); }
non_empty_function_call_parameter_list(A) ::= variable(var). { A = array(var); }
non_empty_function_call_parameter_list(A) ::= AMPERSAND w_variable(var). { A = array(var); }
non_empty_function_call_parameter_list(A) ::= non_empty_function_call_parameter_list(list)
																							COMMA expr_without_variable(e). {
	A = list;
	A[] = e;
}
non_empty_function_call_parameter_list(A) ::= non_empty_function_call_parameter_list(list)
																							COMMA variable(var). {
	A = list;
	A[] = var;
}
non_empty_function_call_parameter_list(A) ::= non_empty_function_call_parameter_list(list)
																							COMMA AMPERSAND w_variable(var). {
	A = list;
	A[] = var;
}

global_var_list ::= global_var_list COMMA global_var.
global_var_list ::= global_var.

global_var ::= T_VARIABLE.
global_var ::= DOLLAR r_variable.
global_var ::= DOLLAR LCURLY expr RCURLY.


static_var_list ::= static_var_list COMMA T_VARIABLE.
static_var_list ::= static_var_list COMMA T_VARIABLE EQUALS static_scalar.
static_var_list ::= T_VARIABLE.
static_var_list ::= T_VARIABLE EQUALS static_scalar.

class_statement_list(A) ::= class_statement_list(list) class_statement(stmt). {
	A = list;
	foreach(stmt as $st)
		A[] = $st;
}
class_statement_list(A) ::= . { A = array(); }

class_statement(A) ::= variable_modifiers(mmodifiers) class_variable_declaration(var) SEMI. {
	A = array();
	$base = new PC_Obj_Field(
		$this->state->get_file(),$this->state->get_line(),
		var->metadata[0]['name']
	);
	$base->set_static(in_array('static',mmodifiers->metadata));
	if(in_array('private',mmodifiers->metadata))
		$base->set_visibility(PC_Obj_Visible::V_PRIVATE);
	else if(in_array('protected',mmodifiers->metadata))
		$base->set_visibility(PC_Obj_Visible::V_PROTECTED);
	else
		$base->set_visibility(PC_Obj_Visible::V_PUBLIC);
	$this->state->parse_field_doc($base);
	
	foreach(var->metadata as $v)
	{
		$field = clone $base;
		$field->set_name($v['name']);
		if(!$field->get_type()->is_multiple() && isset($v['val']))
			$field->set_type($v['val']);
		A[] = $field;
	}
}
class_statement(A) ::= class_constant_declaration(constdecl) SEMI. {
	A = array();
	$base = new PC_Obj_Constant($this->state->get_file(),$this->state->get_line(),'dummy');
	$this->state->parse_const_doc($base);
	
	foreach(constdecl->metadata as $c)
	{
		$const = clone $base;
		$const->set_name($c['name']);
		if(isset($c['val']))
			$const->set_type($c['val']);
		A[] = $const;
	}
}
class_statement(A) ::= method_modifiers(mmodifiers) T_FUNCTION is_reference T_STRING(mname)
		LPAREN parameter_list(mparams) RPAREN method_body. {
	A = array();
	$m = new PC_Obj_Method($this->state->get_file(),$this->state->get_last_function_line(),false);
	$m->set_name(mname);
	$m->set_static(in_array('static',mmodifiers->metadata));
	$m->set_abstract(in_array('abstract',mmodifiers->metadata));
	$m->set_final(in_array('final',mmodifiers->metadata));
	if(in_array('private',mmodifiers->metadata))
		$m->set_visibility(PC_Obj_Visible::V_PRIVATE);
	else if(in_array('protected',mmodifiers->metadata))
		$m->set_visibility(PC_Obj_Visible::V_PROTECTED);
	else
		$m->set_visibility(PC_Obj_Visible::V_PUBLIC);
	foreach(mparams as $param)
		$m->put_param($param);
	$this->state->parse_method_doc($m);
	A[] = $m;
}


method_body ::= SEMI. /* abstract method */
method_body ::= LCURLY inner_statement_list RCURLY.

variable_modifiers(A) ::= non_empty_member_modifiers(mods). { A = new PC_Type_yyToken(mods); }
variable_modifiers(A) ::= T_VAR. { A = new PC_Type_yyToken('',array('public')); }

method_modifiers(A) ::= non_empty_member_modifiers(mods). { A = new PC_Type_yyToken(mods); }
method_modifiers(A) ::= . { A = new PC_Type_yyToken(''); }

non_empty_member_modifiers(A) ::= member_modifier(mod). { A = new PC_Type_yyToken(mod); }
non_empty_member_modifiers(A) ::= non_empty_member_modifiers(mods) member_modifier(mod). {
	A = new PC_Type_yyToken(mods);
	A[] = mod;
}

member_modifier(A) ::= T_PUBLIC|T_PROTECTED|T_PRIVATE|T_STATIC|T_ABSTRACT|T_FINAL(mod). {
	A = new PC_Type_yyToken('',array(mod));
}

class_variable_declaration(A) ::= class_variable_declaration(decl) COMMA T_VARIABLE(varname). {
	A = new PC_Type_yyToken(decl);
	A[] = array(array('name' => substr(varname,1)));
}
class_variable_declaration(A) ::= class_variable_declaration(decl) COMMA T_VARIABLE(varname)
																	EQUALS static_scalar(varval). {
	A = new PC_Type_yyToken(decl);
	A[] = array(array('name' => substr(varname,1),'val' => varval));
}
class_variable_declaration(A) ::= T_VARIABLE(varname). {
	A = new PC_Type_yyToken('',array(array('name' => substr(varname,1))));
}
class_variable_declaration(A) ::= T_VARIABLE(varname) EQUALS static_scalar(varval). {
	A = new PC_Type_yyToken('',array(array('name' => substr(varname,1),'val' => varval)));
}

class_constant_declaration(A) ::= class_constant_declaration(decl) COMMA T_STRING(varname)
																	EQUALS static_scalar(varval). {
	A = new PC_Type_yyToken(decl);
	A[] = array(array('name' => varname,'val' => varval));
}
class_constant_declaration(A) ::= T_CONST T_STRING(varname) EQUALS static_scalar(varval). {
	A = new PC_Type_yyToken('',array(array('name' => varname,'val' => varval)));
}

echo_expr_list ::= echo_expr_list COMMA expr.
echo_expr_list ::= expr.

unset_variables ::= unset_variable.
unset_variables ::= unset_variables COMMA unset_variable.

unset_variable ::= variable.

use_filename ::= T_CONSTANT_ENCAPSED_STRING.
use_filename ::= LCURLY T_CONSTANT_ENCAPSED_STRING RCURLY.

r_variable ::= variable.

w_variable ::= variable.

rw_variable ::= variable.

variable ::= base_variable_with_function_calls T_OBJECT_OPERATOR object_property method_or_not variable_properties.
variable ::= base_variable_with_function_calls.

variable_properties ::= variable_properties variable_property.
variable_properties ::= .

variable_property ::= T_OBJECT_OPERATOR object_property method_or_not.

method_or_not ::= LPAREN function_call_parameter_list RPAREN.
method_or_not ::= .

variable_without_objects ::= reference_variable.
variable_without_objects ::= simple_indirect_reference reference_variable.

static_member ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM variable_without_objects.

base_variable_with_function_calls ::= base_variable.
base_variable_with_function_calls ::= function_call.

base_variable ::= reference_variable.
base_variable ::= simple_indirect_reference reference_variable.
base_variable ::= static_member.
	
reference_variable ::= reference_variable LBRACKET dim_offset RBRACKET.
reference_variable ::= reference_variable LCURLY expr RCURLY.
reference_variable ::= compound_variable.

compound_variable ::= T_VARIABLE.
compound_variable ::= DOLLAR LCURLY expr RCURLY.

dim_offset ::= expr.
dim_offset ::= .

object_property ::= object_dim_list.
object_property ::= variable_without_objects.

object_dim_list ::= object_dim_list LBRACKET dim_offset RBRACKET.
object_dim_list ::= object_dim_list LCURLY expr RCURLY.
object_dim_list ::= variable_name .

variable_name ::= T_STRING.
variable_name ::= LCURLY expr RCURLY.

simple_indirect_reference ::= DOLLAR.
simple_indirect_reference ::= simple_indirect_reference DOLLAR.

assignment_list ::= assignment_list COMMA assignment_list_element.
assignment_list ::= assignment_list_element.

assignment_list_element ::= variable.
assignment_list_element ::= T_LIST LPAREN assignment_list RPAREN.
assignment_list_element ::= .

array_pair_list ::= non_empty_array_pair_list possible_comma.
array_pair_list ::= .

non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr T_DOUBLE_ARROW expr.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr.
non_empty_array_pair_list ::= expr T_DOUBLE_ARROW expr.
non_empty_array_pair_list ::= expr.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA expr T_DOUBLE_ARROW AMPERSAND w_variable.
non_empty_array_pair_list ::= non_empty_array_pair_list COMMA AMPERSAND w_variable.
non_empty_array_pair_list ::= expr T_DOUBLE_ARROW AMPERSAND w_variable.
non_empty_array_pair_list ::= AMPERSAND w_variable.

encaps_list ::= encaps_list encaps_var.
encaps_list ::= encaps_list T_STRING.
encaps_list ::= encaps_list T_NUM_STRING.
encaps_list ::= encaps_list T_ENCAPSED_AND_WHITESPACE.
encaps_list ::= encaps_list T_CHARACTER.
encaps_list ::= encaps_list T_BAD_CHARACTER.
encaps_list ::= encaps_list LBRACKET.
encaps_list ::= encaps_list RBRACKET.
encaps_list ::= encaps_list LCURLY.
encaps_list ::= encaps_list RCURLY.
encaps_list ::= encaps_list T_OBJECT_OPERATOR.
encaps_list ::= .



encaps_var ::= T_VARIABLE.
encaps_var ::= T_VARIABLE LBRACKET encaps_var_offset RBRACKET.
encaps_var ::= T_VARIABLE T_OBJECT_OPERATOR T_STRING.
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES expr RCURLY.
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME LBRACKET expr RBRACKET RCURLY.
encaps_var ::= T_CURLY_OPEN variable RCURLY.

encaps_var_offset ::= T_STRING|T_NUM_STRING|T_VARIABLE.

internal_functions_in_yacc ::= T_ISSET LPAREN isset_variables RPAREN.
internal_functions_in_yacc ::= T_EMPTY LPAREN variable RPAREN.
internal_functions_in_yacc ::= T_INCLUDE expr.
internal_functions_in_yacc ::= T_INCLUDE_ONCE expr.
internal_functions_in_yacc ::= T_EVAL LPAREN expr RPAREN.
internal_functions_in_yacc ::= T_REQUIRE expr.
internal_functions_in_yacc ::= T_REQUIRE_ONCE expr.

isset_variables ::= variable.
isset_variables ::= isset_variables COMMA variable.

class_constant ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM T_STRING.

fully_qualified_class_name(A) ::= T_STRING(name). { A = new PC_Type_yyToken(name); }

function_call ::= T_STRING(name) LPAREN function_call_parameter_list(args) RPAREN. {
	if(strcasecmp(name,'define') == 0)
		$this->state->handle_define(args);
}
function_call ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM T_STRING LPAREN function_call_parameter_list RPAREN.
function_call ::= fully_qualified_class_name T_PAAMAYIM_NEKUDOTAYIM variable_without_objects LPAREN function_call_parameter_list RPAREN.
function_call ::= variable_without_objects LPAREN function_call_parameter_list RPAREN.

scalar(A) ::= T_STRING(str). {
	if(strcasecmp(str,"true") == 0)
		A = PC_Obj_MultiType::create_bool(true);
	else if(strcasecmp(str,"false") == 0)
		A = PC_Obj_MultiType::create_bool(false);
	else 
		A = $this->state->get_constant_type(str);
}
scalar(A) ::= T_STRING_VARNAME. {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= class_constant.
scalar(A) ::= common_scalar(sc). { A = sc; }
scalar(A) ::= DOUBLEQUOTE encaps_list DOUBLEQUOTE. {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= SINGLEQUOTE encaps_list SINGLEQUOTE. {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC encaps_list T_END_HEREDOC. {
	A = PC_Obj_MultiType::create_string();
}

class_name_reference ::= T_STRING.
class_name_reference ::= dynamic_class_name_reference.

dynamic_class_name_reference ::= base_variable T_OBJECT_OPERATOR object_property dynamic_class_name_variable_properties.
dynamic_class_name_reference ::= base_variable.

dynamic_class_name_variable_properties ::= dynamic_class_name_variable_properties dynamic_class_name_variable_property.
dynamic_class_name_variable_properties ::= .

dynamic_class_name_variable_property ::= T_OBJECT_OPERATOR object_property.

ctor_arguments ::= LPAREN function_call_parameter_list RPAREN.
ctor_arguments ::= .

possible_comma ::= COMMA.
possible_comma ::= .

for_expr ::= non_empty_for_expr.
for_expr ::= .

non_empty_for_expr ::= non_empty_for_expr COMMA expr.
non_empty_for_expr ::= expr.

is_reference ::= AMPERSAND.
is_reference ::= .