package Codepot::AuthenHandler;

use strict;
use warnings;

use Apache2::Access ();
use Apache2::RequestUtil ();
use Apache2::RequestRec ();
use Apache2::Log;

use Apache2::Const -compile => qw(OK DECLINED FORBIDDEN HTTP_UNAUTHORIZED  HTTP_INTERNAL_SERVER_ERROR AUTH_REQUIRED);

use Data::Dumper;
sub handler: method
{
	my ($class, $r) = @_;
	return Apache2::Const::OK;
}
1;
