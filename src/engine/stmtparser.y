%name PC_Stmt_
%declare_class {class PC_Engine_StmtParser}

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

%left T_INCLUDE T_INCLUDE_ONCE T_EVAL T_REQUIRE T_REQUIRE_ONCE .
%left COMMA .
%left T_LOGICAL_OR .
%left T_LOGICAL_XOR .
%left T_LOGICAL_AND .
%right T_PRINT .
%right T_YIELD .
%right T_DOUBLE_ARROW .
%right T_YIELD_FROM .
%left EQUALS T_PLUS_EQUAL T_MINUS_EQUAL T_MUL_EQUAL T_DIV_EQUAL T_CONCAT_EQUAL T_MOD_EQUAL T_AND_EQUAL T_OR_EQUAL T_XOR_EQUAL T_SL_EQUAL T_SR_EQUAL T_POW_EQUAL .
%left QUESTION COLON .
%right T_COALESCE .
%left T_BOOLEAN_OR .
%left T_BOOLEAN_AND .
%left BAR .
%left CARAT .
%left AMPERSAND .
%nonassoc T_IS_EQUAL T_IS_NOT_EQUAL T_IS_IDENTICAL T_IS_NOT_IDENTICAL T_SPACESHIP .
%nonassoc LESSTHAN T_IS_SMALLER_OR_EQUAL GREATERTHAN T_IS_GREATER_OR_EQUAL .
%left T_SL T_SR .
%left PLUS MINUS DOT .
%left TIMES DIVIDE PERCENT .
%right EXCLAM .
%nonassoc T_INSTANCEOF .
%right TILDE T_INC T_DEC T_INT_CAST T_DOUBLE_CAST T_STRING_CAST T_ARRAY_CAST T_OBJECT_CAST T_BOOL_CAST T_UNSET_CAST AT .
%right T_POW .
%right LBRACKET .
%nonassoc T_NEW T_CLONE .
%left T_NOELSE .
%left T_ELSEIF .
%left T_ELSE .
%left T_ENDIF .
%right T_STATIC T_ABSTRACT T_FINAL T_PRIVATE T_PROTECTED T_PUBLIC .
%nonassoc SINGLEQUOTE .

start ::= top_statement_list .

reserved_non_modifiers(A) ::= T_INCLUDE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INCLUDE_ONCE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EVAL(t) . { A = t; }
reserved_non_modifiers(A) ::= T_REQUIRE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_REQUIRE_ONCE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_OR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_XOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LOGICAL_AND(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INSTANCEOF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NEW(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLONE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EXIT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_IF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ELSEIF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ELSE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDIF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ECHO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_WHILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDWHILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDFOR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FOREACH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDFOREACH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DECLARE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDDECLARE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_AS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CATCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FINALLY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_THROW(t) . { A = t; }
reserved_non_modifiers(A) ::= T_USE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INSTEADOF(t) . { A = t; }
reserved_non_modifiers(A) ::= T_GLOBAL(t) . { A = t; }
reserved_non_modifiers(A) ::= T_VAR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_UNSET(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ISSET(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EMPTY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CONTINUE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_GOTO(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FUNCTION(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CONST(t) . { A = t; }
reserved_non_modifiers(A) ::= T_RETURN(t) . { A = t; }
reserved_non_modifiers(A) ::= T_PRINT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_YIELD(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LIST(t) . { A = t; }
reserved_non_modifiers(A) ::= T_SWITCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ENDSWITCH(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CASE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DEFAULT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_BREAK(t) . { A = t; }
reserved_non_modifiers(A) ::= T_ARRAY(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CALLABLE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_EXTENDS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_IMPLEMENTS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NAMESPACE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRAIT(t) . { A = t; }
reserved_non_modifiers(A) ::= T_INTERFACE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLASS(t) . { A = t; }
reserved_non_modifiers(A) ::= T_CLASS_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_TRAIT_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FUNC_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_METHOD_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_LINE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_FILE(t) . { A = t; }
reserved_non_modifiers(A) ::= T_DIR(t) . { A = t; }
reserved_non_modifiers(A) ::= T_NS_C(t) . { A = t; }
reserved_non_modifiers(A) ::= T_HALT_COMPILER(t) . { A = t; }

semi_reserved(A) ::= reserved_non_modifiers(t) . { A = t; }
semi_reserved(A) ::= T_STATIC(t) . { A = t; }
semi_reserved(A) ::= T_ABSTRACT(t) . { A = t; }
semi_reserved(A) ::= T_FINAL(t) . { A = t; }
semi_reserved(A) ::= T_PRIVATE(t) . { A = t; }
semi_reserved(A) ::= T_PROTECTED(t) . { A = t; }
semi_reserved(A) ::= T_PUBLIC(t) . { A = t; }

identifier(A) ::= T_STRING(n) . { A = n; }
identifier(A) ::= semi_reserved(t) . { A = t; }

top_statement_list ::= top_statement_list top_statement .
top_statement_list ::= /* empty */ .

namespace_name(A) ::= T_STRING(name) . { A = name; }
namespace_name(A) ::= namespace_name T_NS_SEPARATOR T_STRING(name) . {
	// TODO
	A = name;
}

name(A) ::= namespace_name(name) . { A = name; }
name(A) ::= T_NAMESPACE T_NS_SEPARATOR namespace_name(name) . { A = name; }
name(A) ::= T_NS_SEPARATOR namespace_name(name) . { A = name; }

top_statement ::= statement .
top_statement ::= function_declaration_statement .
top_statement ::= class_declaration_statement .
top_statement ::= trait_declaration_statement .
top_statement ::= interface_declaration_statement .
top_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI .
top_statement ::= T_NAMESPACE namespace_name SEMI .
top_statement ::= T_NAMESPACE namespace_name LCURLY top_statement_list RCURLY .
top_statement ::= T_NAMESPACE LCURLY top_statement_list RCURLY .
top_statement ::= T_USE mixed_group_use_declaration SEMI .
top_statement ::= T_USE use_type group_use_declaration SEMI .
top_statement ::= T_USE use_declarations SEMI .
top_statement ::= T_USE use_type use_declarations SEMI .
top_statement ::= T_CONST const_list SEMI .

use_type ::= T_FUNCTION .
use_type ::= T_CONST .

group_use_declaration ::= namespace_name T_NS_SEPARATOR LCURLY unprefixed_use_declarations RCURLY .
group_use_declaration ::= T_NS_SEPARATOR namespace_name T_NS_SEPARATOR LCURLY unprefixed_use_declarations RCURLY .

mixed_group_use_declaration ::= namespace_name T_NS_SEPARATOR LCURLY inline_use_declarations RCURLY .
mixed_group_use_declaration ::= T_NS_SEPARATOR namespace_name T_NS_SEPARATOR LCURLY inline_use_declarations RCURLY .

inline_use_declarations ::= inline_use_declarations COMMA inline_use_declaration .
inline_use_declarations ::= inline_use_declaration .

unprefixed_use_declarations ::= unprefixed_use_declarations COMMA unprefixed_use_declaration .
unprefixed_use_declarations ::= unprefixed_use_declaration .

use_declarations ::= use_declarations COMMA use_declaration .
use_declarations ::= use_declaration .

inline_use_declaration ::= unprefixed_use_declaration .
inline_use_declaration ::= use_type unprefixed_use_declaration .

unprefixed_use_declaration ::= namespace_name .
unprefixed_use_declaration ::= namespace_name T_AS T_STRING .

use_declaration ::= unprefixed_use_declaration .
use_declaration ::= T_NS_SEPARATOR unprefixed_use_declaration .

const_list ::= const_list COMMA const_decl .
const_list ::= const_decl .

inner_statement_list ::= inner_statement_list inner_statement .
inner_statement_list ::= /* empty */ .

inner_statement ::= statement .
inner_statement ::= function_declaration_statement .
inner_statement ::= class_declaration_statement .
inner_statement ::= trait_declaration_statement .
inner_statement ::= interface_declaration_statement .
inner_statement ::= T_HALT_COMPILER LPAREN RPAREN SEMI .

statement ::= LCURLY inner_statement_list RCURLY .
statement ::= if_stmt . {
    $this->state->end_cond();
}
statement ::= alt_if_stmt . {
    $this->state->end_cond();
}
statement ::= T_WHILE LPAREN expr RPAREN while_statement . {
    $this->state->end_loop();
}
statement ::= T_DO statement T_WHILE LPAREN expr RPAREN SEMI . {
    $this->state->end_loop();
}
statement ::= T_FOR LPAREN for_exprs SEMI for_exprs SEMI for_exprs RPAREN for_statement . {
    $this->state->end_loop();
}
statement ::= T_SWITCH LPAREN expr RPAREN switch_case_list . {
    $this->state->end_cond();
}
statement ::= T_BREAK optional_expr SEMI .
statement ::= T_CONTINUE optional_expr SEMI .
statement ::= T_RETURN optional_expr(s) SEMI . {
    $this->state->add_return(s);
}
statement ::= T_GLOBAL global_var_list SEMI .
statement ::= T_STATIC static_var_list SEMI .
statement ::= T_ECHO echo_expr_list SEMI .
statement ::= T_INLINE_HTML .
statement ::= expr SEMI .
statement ::= T_UNSET LPAREN unset_variables RPAREN SEMI .
statement ::= T_FOREACH LPAREN foreach_inner RPAREN foreach_statement . {
    $this->state->end_loop();
}
statement ::= T_DECLARE LPAREN const_list RPAREN declare_statement .
statement ::= SEMI /* empty statement */ .
statement ::= T_TRY LCURLY inner_statement_list RCURLY catch_list finally_statement . {
    $this->state->end_cond();
}
statement ::= T_THROW expr(e) SEMI . {
    $this->state->add_throw(e);
}
statement ::= T_GOTO T_STRING SEMI .
statement ::= T_STRING COLON .

catch_list ::= /* empty */ .
catch_list ::= catch_head LCURLY inner_statement_list RCURLY .

catch_head ::= catch_list T_CATCH LPAREN name(class) T_VARIABLE(var) RPAREN . {
		$value = PC_Obj_MultiType::create_object(class);
		$vobj = new PC_Obj_Variable(
			$this->state->get_file(),$this->state->get_line(),substr(var,1)
		);
		$this->state->set_var($vobj,$value);
}

foreach_inner ::= expr(e) T_AS foreach_variable(first) . {
		$this->state->set_foreach_var(e,first,null);
}
foreach_inner ::= expr(e) T_AS foreach_variable(first) T_DOUBLE_ARROW foreach_variable(second) . {
		$this->state->set_foreach_var(e,first,second);
}

finally_statement ::= /* empty */ .
finally_statement ::= T_FINALLY LCURLY inner_statement_list RCURLY .

unset_variables ::= unset_variable .
unset_variables ::= unset_variables COMMA unset_variable .

unset_variable ::= variable(var) . {
	$this->state->unset_var(var);
}

function_declaration_statement ::= func_head
																	 LPAREN parameter_list RPAREN
																	 return_type backup_doc_comment
																	 LCURLY inner_statement_list RCURLY . {
		$this->state->end_function();
}

is_reference(A) ::= /* empty */ . { A = false; }
is_reference(A) ::= AMPERSAND . { A = true; }

is_variadic ::= /* empty */ .
is_variadic ::= T_ELLIPSIS .

class_declaration_statement ::= class_head extends_from
																implements_list
																backup_doc_comment LCURLY class_statement_list RCURLY . {
  	$this->state->end_class();
}

class_modifiers ::= T_CLASS .
class_modifiers ::= T_ABSTRACT T_CLASS .
class_modifiers ::= T_FINAL T_CLASS .

trait_declaration_statement ::= T_TRAIT T_STRING backup_doc_comment LCURLY class_statement_list RCURLY .

interface_declaration_statement ::= interface_head interface_extends_list
																		backup_doc_comment LCURLY class_statement_list RCURLY . {
  	$this->state->end_class();
}

extends_from ::= /* empty */ .
extends_from ::= T_EXTENDS name(n) .

interface_extends_list ::= /* empty */ .
interface_extends_list ::= T_EXTENDS name_list(list) .

implements_list ::= /* empty */ .
implements_list ::= T_IMPLEMENTS name_list(list) .

foreach_variable(A) ::= variable(v) . { A = array(v); }
foreach_variable(A) ::= AMPERSAND variable(v) . { A = array(v); }
foreach_variable(A) ::= T_LIST LPAREN assignment_list(list) RPAREN . { A = list; }

for_statement ::= statement .
for_statement ::= COLON inner_statement_list T_ENDFOR SEMI .

foreach_statement ::= statement .
foreach_statement ::= COLON inner_statement_list T_ENDFOREACH SEMI .

declare_statement ::= statement .
declare_statement ::= COLON inner_statement_list T_ENDDECLARE SEMI .

switch_case_list ::= LCURLY case_list RCURLY .
switch_case_list ::= LCURLY SEMI case_list RCURLY .
switch_case_list ::= COLON case_list T_ENDSWITCH SEMI .
switch_case_list ::= COLON SEMI case_list T_ENDSWITCH SEMI .

case_list ::= /* empty */ .
case_list ::= case_list T_CASE expr case_separator inner_statement_list .
case_list ::= case_list T_DEFAULT case_separator inner_statement_list .

case_separator ::= COLON .
case_separator ::= SEMI .

while_statement ::= statement .
while_statement ::= COLON inner_statement_list T_ENDWHILE SEMI .

if_stmt_without_else ::= T_IF LPAREN expr RPAREN statement .
if_stmt_without_else ::= if_stmt_without_else T_ELSEIF LPAREN expr RPAREN statement .

if_stmt ::= if_stmt_without_else .
if_stmt ::= if_stmt_without_else T_ELSE statement .

alt_if_stmt_without_else ::= T_IF LPAREN expr RPAREN COLON inner_statement_list .
alt_if_stmt_without_else ::= alt_if_stmt_without_else T_ELSEIF LPAREN expr RPAREN COLON inner_statement_list .

alt_if_stmt ::= alt_if_stmt_without_else T_ENDIF SEMI .
alt_if_stmt ::= alt_if_stmt_without_else T_ELSE COLON inner_statement_list T_ENDIF SEMI .

parameter_list(A) ::= non_empty_parameter_list(list) . { A = list; }
parameter_list(A) ::= /* empty */ . { A = array(); }

non_empty_parameter_list(A) ::= parameter(p) . {
	A = array();
	A[] = p;
  $this->state->set_func_param(p);
}
non_empty_parameter_list(A) ::= non_empty_parameter_list(list) COMMA parameter(p) . {
	A = list;
	A[] = p;
  $this->state->set_func_param(p);
}

parameter(A) ::= optional_type(vtype) is_reference(ref) is_variadic T_VARIABLE(vname) . {
	A = $this->state->create_parameter(substr(vname,1),vtype,null,false,ref);
}
parameter(A) ::= optional_type(vtype) is_reference(ref) is_variadic T_VARIABLE(vname) EQUALS expr(vval) . {
	A = $this->state->create_parameter(substr(vname,1),vtype,vval,true,ref);
}

optional_type(A) ::= /* empty */ . { A = new PC_Obj_MultiType(); }
optional_type(A) ::= type(t) . { A = t; }

type(A) ::= T_ARRAY . { A = PC_Obj_MultiType::create_array(); }
type(A) ::= T_CALLABLE . { A = PC_Obj_MultiType::create_callable(); }
type(A) ::= name(vtype) . { A = $this->state->get_type_by_name(vtype); }

return_type ::= /* empty */ .
return_type ::= COLON type .

argument_list(A) ::= LPAREN RPAREN . { A = array(); }
argument_list(A) ::= LPAREN non_empty_argument_list(list) RPAREN . { A = list; }

non_empty_argument_list(A) ::= argument(arg) . { A = array(arg); }
non_empty_argument_list(A) ::= non_empty_argument_list(list) COMMA argument(arg) . {
	A = list;
	A[] = arg;
}

argument(A) ::= expr(e) . { A = e; }
argument ::= T_ELLIPSIS expr .

global_var_list ::= global_var_list COMMA global_var .
global_var_list ::= global_var .

global_var ::= simple_variable(var) . {
	$this->state->do_global(var);
}

static_var_list ::= static_var_list COMMA static_var .
static_var_list ::= static_var .

static_var ::= T_VARIABLE(name) . {
  $var = $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
	$this->state->set_var($var,new PC_Obj_MultiType());
}
static_var ::= T_VARIABLE(name) EQUALS expr(e) . {
  $var = $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
	$this->state->set_var($var,e);
}

class_statement_list ::= class_statement_list class_statement .
class_statement_list ::= /* empty */ .

class_statement ::= variable_modifiers property_list SEMI .
class_statement ::= method_modifiers T_CONST class_const_list SEMI .
class_statement ::= T_USE name_list trait_adaptations .
class_statement ::= method_head
										LPAREN parameter_list RPAREN
										return_type backup_doc_comment method_body . {
		$this->state->end_function();
}

name_list ::= name .
name_list ::= name_list COMMA name .

trait_adaptations ::= SEMI .
trait_adaptations ::= LCURLY RCURLY .
trait_adaptations ::= LCURLY trait_adaptation_list RCURLY .

trait_adaptation_list ::= trait_adaptation .
trait_adaptation_list ::= trait_adaptation_list trait_adaptation .

trait_adaptation ::= trait_precedence SEMI .
trait_adaptation ::= trait_alias SEMI .

trait_precedence ::= absolute_trait_method_reference T_INSTEADOF name_list .

trait_alias ::= trait_method_reference T_AS T_STRING .
trait_alias ::= trait_method_reference T_AS reserved_non_modifiers .
trait_alias ::= trait_method_reference T_AS member_modifier identifier .
trait_alias ::= trait_method_reference T_AS member_modifier .

trait_method_reference ::= identifier .
trait_method_reference ::= absolute_trait_method_reference .

absolute_trait_method_reference ::= name T_PAAMAYIM_NEKUDOTAYIM identifier .

method_body ::= SEMI /* abstract method */ .
method_body ::= LCURLY inner_statement_list RCURLY .

variable_modifiers(A) ::= non_empty_member_modifiers(mods) . { A = mods; }
variable_modifiers(A) ::= T_VAR . { A = array('public'); }

method_modifiers(A) ::= /* empty */ . { A = array(); }
method_modifiers(A) ::= non_empty_member_modifiers(mods) . { A = mods; }

non_empty_member_modifiers(A) ::= member_modifier(mod) . { A = array(mod); }
non_empty_member_modifiers(A) ::= non_empty_member_modifiers(mods) member_modifier(mod) . {
	A = mods;
	A[] = mod;
}

member_modifier(A) ::= T_PUBLIC|T_PROTECTED|T_PRIVATE|T_STATIC|T_ABSTRACT|T_FINAL(mod) . {
	A = strtolower(mod);
}

property_list(A) ::= property_list(list) COMMA property(p) . { A = list; A[] = p; }
property_list(A) ::= property(p) . { A = array(p); }

property(A) ::= T_VARIABLE(varname) backup_doc_comment . { A = array('name' => substr(varname,1)); }
property(A) ::= T_VARIABLE(varname) EQUALS expr(varval) backup_doc_comment . {
	A = array('name' => substr(varname,1),'val' => varval);
}

class_const_list(A) ::= class_const_list(list) COMMA class_const_decl(d) . { A = list; A[] = d; }
class_const_list(A) ::= class_const_decl(d) . { A = array(d); }

class_const_decl(A) ::= identifier(cname) EQUALS expr(cvalue) backup_doc_comment . {
	A = array('name' => cname,'val' => cvalue);
}

const_decl ::= T_STRING EQUALS expr backup_doc_comment .

echo_expr_list ::= echo_expr_list COMMA echo_expr .
echo_expr_list ::= echo_expr .

echo_expr ::= expr .

for_exprs ::= /* empty */ .
for_exprs ::= non_empty_for_exprs .

non_empty_for_exprs ::= non_empty_for_exprs COMMA expr .
non_empty_for_exprs ::= expr .

anonymous_class(A) ::= anon_class_head(name) ctor_arguments extends_from implements_list
										backup_doc_comment LCURLY class_statement_list RCURLY . {
    $this->state->end_class();
    A = PC_Obj_MultiType::create_object(name);
}

new_expr(A) ::= T_NEW class_name_reference(name) ctor_arguments(args) . {
    A = $this->state->add_call(
    	name,
    	PC_Obj_MultiType::create_string('__construct'),
    	args
    );
}
new_expr(A) ::= T_NEW anonymous_class(c) . { A = c; }

expr_without_variable(A) ::= T_LIST LPAREN assignment_list(list) RPAREN EQUALS expr(e). {
    A = $this->state->handle_list(list,e);
}
expr_without_variable(A) ::= variable(var) EQUALS expr(e). {
    A = $this->state->set_var(var,e);
}
expr_without_variable(A) ::= variable(var) EQUALS AMPERSAND variable(e). {
    A = $this->state->set_var(var,e->get_type(),true);
}
expr_without_variable(A) ::= T_CLONE expr(e). {
    A = clone e;
}
expr_without_variable(A) ::= variable(var) T_PLUS_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('+',var,e);
}
expr_without_variable(A) ::= variable(var) T_MINUS_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('-',var,e);
}
expr_without_variable(A) ::= variable(var) T_MUL_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('*',var,e);
}
expr_without_variable(A) ::= variable(var) T_POW_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('**',var,e);
}
expr_without_variable(A) ::= variable(var) T_DIV_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('/',var,e);
}
expr_without_variable(A) ::= variable(var) T_CONCAT_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('.',var,e);
}
expr_without_variable(A) ::= variable(var) T_MOD_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('%',var,e);
}
expr_without_variable(A) ::= variable(var) T_AND_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('&',var,e);
}
expr_without_variable(A) ::= variable(var) T_OR_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('|',var,e);
}
expr_without_variable(A) ::= variable(var) T_XOR_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('^',var,e);
}
expr_without_variable(A) ::= variable(var) T_SL_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('<<',var,e);
}
expr_without_variable(A) ::= variable(var) T_SR_EQUAL expr(e). {
    A = $this->state->handle_bin_assign_op('>>',var,e);
}
expr_without_variable(A) ::= variable(var) T_INC. {
    A = $this->state->handle_post_op('+',var);
}
expr_without_variable(A) ::= T_INC variable(var). {
    A = $this->state->handle_pre_op('+',var);
}
expr_without_variable(A) ::= variable(var) T_DEC. {
    A = $this->state->handle_post_op('-',var);
}
expr_without_variable(A) ::= T_DEC variable(var). {
    A = $this->state->handle_pre_op('-',var);
}
expr_without_variable(A) ::= expr(e1) T_BOOLEAN_OR expr(e2). {
    A = $this->state->handle_bin_op('||',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_BOOLEAN_AND expr(e2). {
    A = $this->state->handle_bin_op('&&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_OR expr(e2). {
    A = $this->state->handle_bin_op('||',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_AND expr(e2). {
    A = $this->state->handle_bin_op('&&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_LOGICAL_XOR expr(e2). {
    A = $this->state->handle_bin_op('xor',e1,e2);
}
expr_without_variable(A) ::= expr(e1) BAR expr(e2). {
    A = $this->state->handle_bin_op('|',e1,e2);
}
expr_without_variable(A) ::= expr(e1) AMPERSAND expr(e2). {
    A = $this->state->handle_bin_op('&',e1,e2);
}
expr_without_variable(A) ::= expr(e1) CARAT expr(e2). {
    A = $this->state->handle_bin_op('^',e1,e2);
}
expr_without_variable(A) ::= expr(e1) DOT expr(e2). {
    A = $this->state->handle_bin_op('.',e1,e2);
}
expr_without_variable(A) ::= expr(e1) PLUS expr(e2). {
    A = $this->state->handle_bin_op('+',e1,e2);
}
expr_without_variable(A) ::= expr(e1) MINUS expr(e2). {
    A = $this->state->handle_bin_op('-',e1,e2);
}
expr_without_variable(A) ::= expr(e1) TIMES expr(e2). {
    A = $this->state->handle_bin_op('*',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_POW expr(e2). {
    A = $this->state->handle_bin_op('**',e1,e2);
}
expr_without_variable(A) ::= expr(e1) DIVIDE expr(e2). {
    A = $this->state->handle_bin_op('/',e1,e2);
}
expr_without_variable(A) ::= expr(e1) PERCENT expr(e2). {
    A = $this->state->handle_bin_op('%',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_SL expr(e2). {
    A = $this->state->handle_bin_op('<<',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_SR expr(e2). {
    A = $this->state->handle_bin_op('>>',e1,e2);
}
expr_without_variable(A) ::= PLUS expr(e). {
    A = $this->state->handle_unary_op('+',e);
}
expr_without_variable ::= PLUS expr T_INC . [T_INC]
expr_without_variable(A) ::= MINUS expr(e). {
    A = $this->state->handle_unary_op('-',e);
}
expr_without_variable ::= MINUS expr T_INC . [T_INC]
expr_without_variable(A) ::= EXCLAM expr(e). {
    A = $this->state->handle_unary_op('!',e);
}
expr_without_variable(A) ::= TILDE expr(e). {
    A = $this->state->handle_unary_op('~',e);
}
expr_without_variable(A) ::= expr(e1) T_IS_IDENTICAL expr(e2). {
    A = $this->state->handle_cmp('===',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_NOT_IDENTICAL expr(e2). {
    A = $this->state->handle_cmp('!==',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_EQUAL expr(e2). {
    A = $this->state->handle_cmp('==',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_NOT_EQUAL expr(e2). {
    A = $this->state->handle_cmp('!=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) LESSTHAN expr(e2). {
    A = $this->state->handle_cmp('<',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_SMALLER_OR_EQUAL expr(e2). {
    A = $this->state->handle_cmp('<=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) GREATERTHAN expr(e2). {
    A = $this->state->handle_cmp('>',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_IS_GREATER_OR_EQUAL expr(e2). {
    A = $this->state->handle_cmp('>=',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_SPACESHIP expr(e2). {
    A = $this->state->handle_cmp('<>',e1,e2);
}
expr_without_variable(A) ::= expr(e1) T_INSTANCEOF class_name_reference(name). {
    A = $this->state->handle_instanceof(e1,name);
}
expr_without_variable(A) ::= LPAREN expr(e) RPAREN. {
    A = e;
}
expr_without_variable(A) ::= new_expr(e) . { A = e; }
expr_without_variable(A) ::= expr(e1) QUESTION expr(e2) COLON expr(e3). {
    A = $this->state->handle_tri_op(e1,e2,e3);
}
expr_without_variable(A) ::= expr(e1) QUESTION COLON expr(e2) . {
    A = $this->state->handle_cmp('?:',e1,e2);
}
expr_without_variable ::= expr T_COALESCE expr .
expr_without_variable(A) ::= internal_functions_in_yacc(e). {
    A = e;
}
expr_without_variable(A) ::= T_INT_CAST expr(e). {
    A = $this->state->handle_cast('int',e);
}
expr_without_variable(A) ::= T_DOUBLE_CAST expr(e). {
    A = $this->state->handle_cast('float',e);
}
expr_without_variable(A) ::= T_STRING_CAST expr(e). {
    A = $this->state->handle_cast('string',e);
}
expr_without_variable(A) ::= T_ARRAY_CAST expr(e). {
    A = $this->state->handle_cast('array',e);
}
expr_without_variable(A) ::= T_OBJECT_CAST expr(e). {
    A = $this->state->handle_cast('object',e);
}
expr_without_variable(A) ::= T_BOOL_CAST expr(e). {
    A = $this->state->handle_cast('bool',e);
}
expr_without_variable(A) ::= T_UNSET_CAST expr(e). {
    A = $this->state->handle_cast('unset',e);
}
expr_without_variable(A) ::= T_EXIT exit_expr. {
		// to support things like <expr> or die
		A = new PC_Obj_MultiType();
}
expr_without_variable(A) ::= AT expr(e). { A = e; }
expr_without_variable(A) ::= scalar(sc). { A = sc; }
expr_without_variable(A) ::= BACKQUOTE backticks_expr BACKQUOTE . {
    A = PC_Obj_MultiType::create_string();
}
expr_without_variable(A) ::= T_PRINT expr. { A = null; }
expr_without_variable ::= T_YIELD .
expr_without_variable ::= T_YIELD expr .
expr_without_variable ::= T_YIELD expr T_DOUBLE_ARROW expr .
expr_without_variable ::= T_YIELD_FROM expr .
expr_without_variable(A) ::= anon_func_head LPAREN parameter_list RPAREN
													lexical_vars return_type backup_doc_comment
													LCURLY inner_statement_list RCURLY . {
    A = PC_Obj_MultiType::create_callable();
    $this->state->end_function();
}
expr_without_variable(A) ::= T_STATIC anon_func_head LPAREN parameter_list RPAREN
													lexical_vars return_type backup_doc_comment
													LCURLY inner_statement_list RCURLY . {
    A = PC_Obj_MultiType::create_callable();
    $this->state->end_function();
}

function ::= T_FUNCTION .

func_head ::= function returns_ref T_STRING(name) . {
	$this->state->start_function(name);
}
method_head ::= method_modifiers function returns_ref identifier(name) . {
	$this->state->start_function(name);
}
anon_func_head ::= function returns_ref . {
	$this->state->start_function();
}

interface_head ::= T_INTERFACE T_STRING(name) . {
	$this->state->start_class(name);
}
class_head ::= class_modifiers T_STRING(name) . {
	$this->state->start_class(name);
}
anon_class_head(A) ::= T_CLASS . {
	A = $this->state->start_class();
}

backup_doc_comment ::= /* empty */ .

returns_ref ::= /* empty */ .
returns_ref ::= AMPERSAND .

lexical_vars ::= /* empty */ .
lexical_vars ::= T_USE LPAREN lexical_var_list RPAREN .

lexical_var_list ::= lexical_var_list COMMA lexical_var(var) . {
	$this->state->set_func_param(new PC_Obj_Parameter(var->get_name(),var->get_type()));
}
lexical_var_list ::= lexical_var(var) . {
	$this->state->set_func_param(new PC_Obj_Parameter(var->get_name(),var->get_type()));
}

lexical_var(A) ::= T_VARIABLE(name) . {
    A = $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)),true);
}
lexical_var(A) ::= AMPERSAND T_VARIABLE(name) . {
    A = $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)),true);
}

function_call(A) ::= name(name) argument_list(args) . {
    $fname = PC_Obj_MultiType::create_string(name);
    A = $this->state->add_call(null,$fname,args);
}
function_call(A) ::= class_name(classname) T_PAAMAYIM_NEKUDOTAYIM member_name(funcname) argument_list(args) . {
    A = $this->state->add_call(PC_Obj_MultiType::create_string(classname),funcname,args,true);
}
function_call(A) ::= variable_class_name(classname) T_PAAMAYIM_NEKUDOTAYIM member_name(funcname) argument_list(args) . {
    A = $this->state->add_call(classname,funcname,args,true);
}
function_call(A) ::= callable_expr(expr) argument_list(args) . {
	  A = $this->state->add_call(null,expr,args);
}

class_name(A) ::= T_STATIC . { A = 'static'; }
class_name(A) ::= name(n) . { A = n; }

class_name_reference(A) ::= class_name(n) . { A = PC_Obj_MultiType::create_string(n); }
class_name_reference(A) ::= new_variable(v) . { A = v->get_type(); }

exit_expr ::= /* empty */ .
exit_expr ::= LPAREN optional_expr RPAREN .

backticks_expr ::= /* empty */ .
backticks_expr ::= T_ENCAPSED_AND_WHITESPACE .
backticks_expr ::= encaps_list .

ctor_arguments(A) ::= /* empty */ . { A = array(); }
ctor_arguments(A) ::= argument_list(list) . { A = list; }

dereferencable_scalar(A) ::= T_ARRAY LPAREN array_pair_list(list) RPAREN . { A = list; }
dereferencable_scalar(A) ::= LBRACKET array_pair_list(list) RBRACKET . { A = list; }
dereferencable_scalar(A) ::= T_CONSTANT_ENCAPSED_STRING(sval) . {
	A = PC_Obj_MultiType::create_string(substr(sval,1,-1));
}

scalar(A) ::= T_LNUMBER(sval) . { A = PC_Obj_MultiType::create_int(sval); }
scalar(A) ::= T_DNUMBER(sval) . { A = PC_Obj_MultiType::create_float(sval); }
scalar(A) ::= T_LINE . { A = PC_Obj_MultiType::create_int($this->state->get_line()); }
scalar(A) ::= T_FILE . { A = PC_Obj_MultiType::create_string($this->state->get_file()); }
scalar(A) ::= T_DIR . {
	// TODO value
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_TRAIT_C|T_METHOD_C|T_FUNC_C|T_NS_C|T_CLASS_C(part). {
  A = $this->state->get_scope_part(part);
}
scalar(A) ::= T_START_HEREDOC T_ENCAPSED_AND_WHITESPACE T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= DOUBLEQUOTE encaps_list DOUBLEQUOTE . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= T_START_HEREDOC encaps_list T_END_HEREDOC . {
	A = PC_Obj_MultiType::create_string();
}
scalar(A) ::= dereferencable_scalar(s) . { A = s; }
scalar(A) ::= constant(c) . { A = c; }

constant(A) ::= name(str) . {
		A = $this->state->handle_constant(str);
}
constant(A) ::= class_name(class) T_PAAMAYIM_NEKUDOTAYIM identifier(const) . {
		A = $this->state->handle_classconst_access(PC_Obj_MultiType::create_string(class),const);
}
constant(A) ::= variable_class_name(class) T_PAAMAYIM_NEKUDOTAYIM identifier(const) . {
		A = $this->state->handle_classconst_access(class,const);
}

possible_comma ::= /* empty */ .
possible_comma ::= COMMA .

expr(A) ::= variable(var) . { A = var->get_type(); }
expr(A) ::= expr_without_variable(e) . { A = e; }

optional_expr(A) ::= /* empty */ . { A = null; }
optional_expr(A) ::= expr(e) . { A = e; }

variable_class_name(A) ::= dereferencable(d) . { A = d->get_type(); }

dereferencable(A) ::= variable(v) . { A = v; }
dereferencable(A) ::= LPAREN expr(e) RPAREN . { A = e; }
dereferencable(A) ::= dereferencable_scalar(s) . { A = s; }

callable_expr(A) ::= callable_variable(v) . { A = v->get_type(); }
callable_expr(A) ::= LPAREN expr(e) RPAREN . { A = e; }
callable_expr(A) ::= dereferencable_scalar(e) . { A = e; }

callable_variable(A) ::= simple_variable(v) . { A = v; }
callable_variable(A) ::= dereferencable(v) LBRACKET optional_expr(off) RBRACKET . {
    A = $this->state->handle_array_access(v,off);
}
callable_variable ::= constant LBRACKET optional_expr RBRACKET .
callable_variable(A) ::= dereferencable(v) LCURLY expr(off) RCURLY . {
    A = $this->state->handle_array_access(v,off);
}
callable_variable(A) ::= dereferencable(obj) T_OBJECT_OPERATOR property_name(vprop) argument_list(a) . {
    $chain = array(array(
        'prop' => vprop,
        'args' => a
    ));
    A = $this->state->handle_object_prop_chain(obj,$chain);
}
callable_variable(A) ::= function_call(call) . {
	A = new PC_Obj_Variable($this->state->get_file(),$this->state->get_line(),'',call);
}

variable(A) ::= callable_variable(v) . { A = v; }
variable(A) ::= static_member(m) . { A = m; }
variable(A) ::= dereferencable(obj) T_OBJECT_OPERATOR property_name(vprop) . {
    $chain = array(array(
        'prop' => vprop,
        'args' => null
    ));
    A = $this->state->handle_object_prop_chain(obj,$chain);
}

simple_variable(A) ::= T_VARIABLE(name). {
    A = $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
}
simple_variable(A) ::= DOLLAR LCURLY expr(e) RCURLY . {
    A = $this->state->get_var(e);
}
simple_variable(A) ::= DOLLAR simple_variable(var) . {
    A = $this->state->get_var(var->get_type());
}

static_member(A) ::= class_name(name) T_PAAMAYIM_NEKUDOTAYIM simple_variable(var) . {
    A = $this->state->handle_field_access(PC_Obj_MultiType::create_string(name),var->get_name());
}
static_member(A) ::= variable_class_name(name) T_PAAMAYIM_NEKUDOTAYIM simple_variable(var) . {
    A = $this->state->handle_field_access(name,var->get_name());
}

new_variable(A) ::= simple_variable(v) . { A = v; }
new_variable(A) ::= new_variable(v) LBRACKET optional_expr(off) RBRACKET . {
	A = $this->state->handle_array_access(v,off);
}
new_variable(A) ::= new_variable(v) LCURLY expr(off) RCURLY . {
	A = $this->state->handle_array_access(v,off);
}
new_variable(A) ::= new_variable(obj) T_OBJECT_OPERATOR property_name(vprop) . {
    $chain = array(array(
        'prop' => vprop,
        'args' => null
    ));
    A = $this->state->handle_object_prop_chain(obj,$chain);
}
new_variable(A) ::= class_name(name) T_PAAMAYIM_NEKUDOTAYIM simple_variable(var) . {
    A = $this->state->handle_field_access(name,var->get_name());
}
new_variable(A) ::= new_variable(v) T_PAAMAYIM_NEKUDOTAYIM simple_variable(var) . {
    A = $this->state->handle_field_access(v,var->get_name());
}

member_name(A) ::= identifier(i) . { A = PC_Obj_MultiType::create_string(i); }
member_name(A) ::= LCURLY expr(e) RCURLY . { A = e; }
member_name(A) ::= simple_variable(v) . { A = v->get_type(); }

property_name(A) ::= T_STRING(s) . {
    A = array(array('type' => 'name','data' => PC_Obj_MultiType::create_string(s)));
}
property_name(A) ::= LCURLY expr(e) RCURLY . {
		A = array(array('type' => 'name','data' => e));
}
property_name(A) ::= simple_variable(var) . {
    A = array(array('type' => 'name','data' => var->get_type()));
}

assignment_list(A) ::= assignment_list(list) COMMA assignment_list_element(el) . {
    A = list;
    A[] = el;
}
assignment_list(A) ::= assignment_list_element(el) . {
	A = array(el);
}

assignment_list_element(A) ::= variable(v) . { A = v; }
assignment_list_element(A) ::= T_LIST LPAREN assignment_list(list) RPAREN . { A = list; }
assignment_list_element(A) ::= /* empty */ . { A = null; }

array_pair_list(A) ::= /* empty */ . { A = PC_Obj_MultiType::create_array(array()); }
array_pair_list(A) ::= non_empty_array_pair_list(list) possible_comma . { A = list; }

non_empty_array_pair_list(A) ::= non_empty_array_pair_list(list) COMMA array_pair(p) . {
	A = list;
	A->get_first()->set_array_type(p['key'],p['val'],p['append']);
}
non_empty_array_pair_list(A) ::= array_pair(p) . {
	A = PC_Obj_MultiType::create_array(array());
	A->get_first()->set_array_type(p['key'],p['val'],p['append']);
}

array_pair(A) ::= expr(skey) T_DOUBLE_ARROW expr(sval) . {
	A = array(
		'key' => skey,
		'val' => sval,
		'append' => false,
	);
}
array_pair(A) ::= expr(sval) . {
	A = array(
		'key' => 0,
		'val' => sval,
		'append' => true,
	);
}
array_pair ::= expr T_DOUBLE_ARROW AMPERSAND variable .
array_pair(A) ::= AMPERSAND variable .

encaps_list ::= encaps_list encaps_var .
encaps_list ::= encaps_list T_ENCAPSED_AND_WHITESPACE .
encaps_list ::= encaps_var .
encaps_list ::= T_ENCAPSED_AND_WHITESPACE encaps_var .

encaps_var ::= T_VARIABLE(name) . {
  $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
}
encaps_var ::= T_VARIABLE(name) LBRACKET encaps_var_offset RBRACKET . {
  $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
}
encaps_var ::= T_VARIABLE(name) T_OBJECT_OPERATOR T_STRING . {
  $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
}
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES expr RCURLY .
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME RCURLY .
encaps_var ::= T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME LBRACKET expr RBRACKET RCURLY .
encaps_var ::= T_CURLY_OPEN variable RCURLY .

encaps_var_offset ::= T_STRING .
encaps_var_offset ::= T_NUM_STRING .
encaps_var_offset ::= T_VARIABLE(name) . {
  $this->state->get_var(PC_Obj_MultiType::create_string(substr(name,1)));
}

internal_functions_in_yacc(A) ::= T_ISSET LPAREN isset_variables RPAREN . {
	A = PC_Obj_MultiType::create_bool();
}
internal_functions_in_yacc(A) ::= T_EMPTY LPAREN expr RPAREN . {
	A = PC_Obj_MultiType::create_bool();
}
internal_functions_in_yacc(A) ::= T_INCLUDE expr . {
	// TODO
	A = new PC_Obj_MultiType();
}
internal_functions_in_yacc(A) ::= T_INCLUDE_ONCE expr . {
	// TODO
	A = new PC_Obj_MultiType();
}
internal_functions_in_yacc(A) ::= T_EVAL LPAREN expr RPAREN . {
	// TODO
	A = new PC_Obj_MultiType();
}
internal_functions_in_yacc(A) ::= T_REQUIRE expr . {
	// TODO
	A = new PC_Obj_MultiType();
}
internal_functions_in_yacc(A) ::= T_REQUIRE_ONCE expr . {
	// TODO
	A = new PC_Obj_MultiType();
}

isset_variables ::= isset_variable .
isset_variables ::= isset_variables COMMA isset_variable .

isset_variable ::= expr .
