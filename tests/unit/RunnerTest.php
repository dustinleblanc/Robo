<?php
use Robo\Config;
use Symfony\Component\Console\Output\BufferedOutput;

class RunnerTest extends \Codeception\TestCase\Test
{
    /**
     * @var \Robo\Runner
     */
    private $runner;

    /**
     * @var \CodeGuy
     */
    protected $guy;

    public function _before()
    {
        $this->runner = new \Robo\Runner();
    }

    public function testHandleError()
    {
        $tmpLevel = error_reporting();

        $this->assertFalse($this->runner->handleError());
        error_reporting(0);
        $this->assertTrue($this->runner->handleError());

        error_reporting($tmpLevel);
    }

    public function testErrorIsHandled()
    {
        $tmpLevel = error_reporting();

        // Set error_get_last to a known state.  Note that it can never be
        // reset; see http://php.net/manual/en/function.error-get-last.php
        @trigger_error('control');
        $error_description = error_get_last();
        $this->assertEquals('control', $error_description['message']);
        @trigger_error('');
        $error_description = error_get_last();
        $this->assertEquals('', $error_description['message']);

        // Set error_reporting to a non-zero value.  In this instance,
        // 'trigger_error' would abort our test script, so we use
        // @trigger_error so that execution will continue.  With our
        // error handler in place, the value of error_get_last() does
        // not change.
        error_reporting(E_USER_ERROR);
        set_error_handler(array($this->runner, 'handleError'));
        @trigger_error('test error', E_USER_ERROR);
        $error_description = error_get_last();
        $this->assertEquals('', $error_description['message']);

        // Set error_reporting to zero.  Now, even 'trigger_error'
        // does not abort execution.  The value of error_get_last()
        // still does not change.
        error_reporting(0);
        trigger_error('test error 2', E_USER_ERROR);
        $error_description = error_get_last();
        $this->assertEquals('', $error_description['message']);

        error_reporting($tmpLevel);
    }

    public function testRunnerNoSuchCommand()
    {
        $argv = ['placeholder', 'no-such-command'];
        $this->runner->execute($argv);
        $this->guy->seeInOutput('Command "no-such-command" is not defined.');
    }

    public function testRunnerList()
    {
        $argv = ['placeholder', 'list'];
        $this->runner->execute($argv);
        $this->guy->seeInOutput('try:array-args');
    }

    public function testRunnerTryArgs()
    {
        $argv = ['placeholder', 'try:array-args', 'a', 'b', 'c'];
        $this->runner->execute($argv);

        $expected = <<<EOT
➜  The parameters passed are:
array (
  0 => 'a',
  1 => 'b',
  2 => 'c',
)

EOT;
        $this->guy->seeOutputEquals($expected);
    }
}
