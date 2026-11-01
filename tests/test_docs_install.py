"""Guide deployment preserves reader credentials and fails closed on drift or a failed swap."""
import importlib.util
from pathlib import Path
import tempfile
import tarfile
import unittest
from unittest.mock import patch

MODULE = Path(__file__).parents[1] / 'docs-site/install-release.py'
spec = importlib.util.spec_from_file_location('docs_install', MODULE)
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)

class GuideInstall(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.target = self.root/'live'; self.target.mkdir()
        for name in (*release.HANDLERS, 'users.php'):
            (self.target/name).write_text('old '+name)
        (self.target/'_private').mkdir();(self.target/'_private/index.html').write_text('old reader page')
        self.expected = release.snapshot(self.target)
        self.stage = self.root/'stage';self.stage.mkdir()
        for name in release.HANDLERS:(self.stage/name).write_text('new '+name)
        for audience in ['crystal','admin']:
            p=self.stage/'_private'/audience;p.mkdir(parents=True);(p/'index.html').write_text('new '+audience)
        (self.stage/'_private/.htaccess').write_text('Require all denied\n')

    def test_install_preserves_readers_and_backs_up_previous_tree(self):
        result=release.install(self.stage,self.target,self.root/'backup',self.expected,lint=False)
        self.assertTrue(result['reader_credentials_preserved'])
        self.assertEqual((self.target/'users.php').read_text(),'old users.php')
        self.assertEqual((self.root/'backup/_private/index.html').read_text(),'old reader page')
        self.assertEqual((self.target/'_private/admin/index.html').read_text(),'new admin')

    def test_preimage_drift_changes_nothing(self):
        (self.target/'gate.php').write_text('other release')
        with self.assertRaisesRegex(RuntimeError,'changed'):
            release.install(self.stage,self.target,self.root/'backup',self.expected,lint=False)
        self.assertEqual((self.target/'auth.php').read_text(),'old auth.php')
        self.assertEqual((self.target/'gate.php').read_text(),'other release')
        self.assertFalse((self.root/'backup').exists())

    def test_staged_credentials_are_refused(self):
        (self.stage/'users.php').write_text('wrong readers')
        with self.assertRaisesRegex(RuntimeError,'credentials'):
            release.install(self.stage,self.target,self.root/'backup',self.expected,lint=False)
        self.assertEqual(release.snapshot(self.target),self.expected)

    def test_failed_new_tree_swap_restores_tree_and_handlers(self):
        replace=release.os.replace
        def fail_new_tree(src,dst):
            if Path(src)==self.stage/'_private':raise OSError('synthetic swap failure')
            return replace(src,dst)
        with patch.object(release.os,'replace',side_effect=fail_new_tree):
            with self.assertRaisesRegex(OSError,'synthetic'):
                release.install(self.stage,self.target,self.root/'backup',self.expected,lint=False)
        self.assertEqual(release.snapshot(self.target),self.expected)

    def test_archive_rejects_path_escape_links_and_credentials(self):
        for index,(name,kind) in enumerate([('../escape','file'),('users.php','file'),('_private/link','link')]):
            archive=self.root/f'bad-{index}.tgz'
            with tarfile.open(archive,'w:gz') as output:
                entry=tarfile.TarInfo(name)
                if kind=='link':entry.type=tarfile.SYMTYPE;entry.linkname='/etc/passwd'
                output.addfile(entry)
            with self.subTest(name=name):
                with self.assertRaises(RuntimeError):release.unpack(archive,self.root/f'unpack-{index}')
        self.assertFalse((self.root/'escape').exists())

if __name__=='__main__':unittest.main(verbosity=2)
